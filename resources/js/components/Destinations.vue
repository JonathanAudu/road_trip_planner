<template>
  <div class="container">
    <h1>Road Trip Planner  </h1>
    <form @submit.prevent="addDestination" class="form">
      <input
        v-model="newDestination.name"
        placeholder="Destination Name"
        required
        class="input"
      />
      <input
        v-model="newDestination.latitude"
        placeholder="Latitude"
        required
        class="input"
      />
      <input
        v-model="newDestination.longitude"
        placeholder="Longitude"
        required
        class="input"
      />
      <button type="submit" class="button">Add Destination</button>
    </form>
    <ul class="destination-list">
      <li
        v-for="(destination, index) in destinations"
        :key="destination.id"
        class="destination-item"
      >
        <div class="destination-info">
          <strong>{{ destination.name }}</strong> - {{ destination.latitude }},
          {{ destination.longitude }}
        </div>
        <div class="destination-actions">
          <button @click="removeDestination(destination.id)" class="button button-remove">
            Remove
          </button>
          <div v-if="index > 0" class="destination-details">
            Distance: {{ destination.distance }} km, Time: {{ destination.time }} min
          </div>
        </div>
      </li>
    </ul>
    <div id="map" class="map"></div>
  </div>
</template>

<script>
import axios from "axios";
import L from "leaflet";

export default {
  data() {
    return {
      newDestination: {
        name: "",
        latitude: "",
        longitude: "",
      },
      destinations: [],
      map: null,
      markers: [],
      routeLayer: null,
    };
  },
  methods: {
    addDestination() {
      axios.post("/destinations", this.newDestination).then((response) => {
        this.destinations.push(response.data);
        this.newDestination = { name: "", latitude: "", longitude: "" };
        this.addMarker(response.data);
        if (this.destinations.length > 1) {
          this.calculateRoute();
        }
      });
    },
    removeDestination(id) {
      axios.delete(`/destinations/${id}`).then(() => {
        this.destinations = this.destinations.filter(
          (destination) => destination.id !== id
        );
        this.removeMarker(id);
        if (this.destinations.length > 1) {
          this.calculateRoute();
        } else if (this.routeLayer) {
          this.map.removeLayer(this.routeLayer);
          this.routeLayer = null;
        }
      });
    },

    initializeMap() {
      delete L.Icon.Default.prototype._getIconUrl;

      L.Icon.Default.mergeOptions({
        iconRetinaUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon-2x.png",
        iconUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png",
        shadowUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png",
      });

      this.map = L.map("map").setView([0, 0], 2);

      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution:
          '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      }).addTo(this.map);

      this.destinations.forEach((destination) => {
        this.addMarker(destination);
      });

      this.map.on("click", this.onMapClick);
    },
    onMapClick(event) {
      const { lat, lng } = event.latlng;
      this.newDestination.latitude = lat.toFixed(6);
      this.newDestination.longitude = lng.toFixed(6);
      this.getPlaceName(lat, lng);
    },
    getPlaceName(lat, lng) {
      const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`;

      axios
        .get(url)
        .then((response) => {
          if (response.data && response.data.display_name) {
            this.newDestination.name = response.data.display_name;
          } else {
            this.newDestination.name = "Unknown place";
          }
        })
        .catch((error) => {
          console.error("Error fetching place name:", error);
          this.newDestination.name = "Unknown place";
        });
    },
    addMarker(destination) {
      const marker = L.marker([destination.latitude, destination.longitude])
        .addTo(this.map)
        .bindPopup(destination.name);
      marker.id = destination.id;
      this.markers.push(marker);
    },
    removeMarker(id) {
      const marker = this.markers.find((m) => m.id === id);
      if (marker) {
        this.map.removeLayer(marker);
        this.markers = this.markers.filter((m) => m.id !== id);
      }
    },
    calculateRoute(retryCount = 0) {
      const coordinates = this.destinations.map((d) => [
        parseFloat(d.longitude),
        parseFloat(d.latitude),
      ]);

      axios
        .post(
          "https://api.openrouteservice.org/v2/directions/driving-car",
          {
            coordinates,
            options: {
              radiuses: coordinates.map(() => 500),
            },
          },
          {
            headers: {
              Authorization: `Bearer ${process.env.VUE_APP_OPENROUTESERVICE_API_KEY}`,
              "Content-Type": "application/json",
            },
          }
        )
        .then((response) => {
          const routes = response.data.routes[0].segments;
          const polylineCoordinates = response.data.routes[0].geometry.coordinates.map(
            (coord) => [coord[1], coord[0]]
          );

          if (this.routeLayer) {
            this.map.removeLayer(this.routeLayer);
          }

          this.routeLayer = L.polyline(polylineCoordinates, { color: "blue" }).addTo(
            this.map
          );

          this.destinations = this.destinations.map((destination, index) => {
            if (index === 0) return destination;
            destination.distance = (routes[index - 1].distance / 1000).toFixed(2);
            destination.time = (routes[index - 1].duration / 60).toFixed(2);
            return destination;
          });
        })
        .catch((error) => {
          console.error("Error calculating route:", error);
          if (
            error.response &&
            error.response.data &&
            error.response.data.error &&
            retryCount < 3
          ) {
            this.adjustCoordinatesAndRetry(coordinates, retryCount);
          } else {
            console.error("Unable to find a routable point.");
          }
        });
    },
    adjustCoordinatesAndRetry(coordinates, retryCount) {
      const adjustedCoordinates = coordinates.map((coord) => [
        coord[0] + (Math.random() - 0.5) * 0.001,
        coord[1] + (Math.random() - 0.5) * 0.001,
      ]);

      axios
        .post(
          "https://api.openrouteservice.org/v2/directions/driving-car",
          {
            coordinates: adjustedCoordinates,
            options: {
              radiuses: adjustedCoordinates.map(() => 500), // Increase the radius to 500 meters
            },
          },
          {
            headers: {
              Authorization: `Bearer ${process.env.VUE_APP_OPENROUTESERVICE_API_KEY}`,
              "Content-Type": "application/json",
            },
          }
        )
        .then((response) => {
          const routes = response.data.routes[0].segments;
          const polylineCoordinates = response.data.routes[0].geometry.coordinates.map(
            (coord) => [coord[1], coord[0]]
          );

          if (this.routeLayer) {
            this.map.removeLayer(this.routeLayer);
          }

          this.routeLayer = L.polyline(polylineCoordinates, { color: "blue" }).addTo(
            this.map
          );

          this.destinations = this.destinations.map((destination, index) => {
            if (index === 0) return destination;
            destination.distance = (routes[index - 1].distance / 1000).toFixed(2);
            destination.time = (routes[index - 1].duration / 60).toFixed(2);
            return destination;
          });
        })
        .catch((error) => {
          console.error("Error calculating route:", error);
          if (retryCount < 3) {
            this.calculateRoute(retryCount + 1);
          }
        });
    },
  },
  mounted() {
    axios.get("/destinations").then((response) => {
      this.destinations = response.data;
      this.initializeMap();
      if (this.destinations.length > 1) {
        this.calculateRoute();
      }
    });
  },
};
</script>

<style>
.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  text-align: center;
}

.form {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
  margin-bottom: 20px;
}

.input {
  flex: 1;
  padding: 10px;
  font-size: 16px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.button {
  padding: 10px 20px;
  font-size: 16px;
  color: #fff;
  background-color: #007bff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.button-remove {
  background-color: #dc3545;
}

.button:hover {
  background-color: #0056b3;
}

.button-remove:hover {
  background-color: #c82333;
}

.destination-list {
  list-style-type: none;
  padding: 0;
}

.destination-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  margin-bottom: 10px;
  background-color: #f9f9f9;
}

.destination-info {
  flex: 1;
}

.destination-actions {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.destination-details {
  margin-top: 5px;
}

.map {
  height: 500px;
  width: 100%;
  border: 1px solid #ccc;
  border-radius: 4px;
  overflow: hidden;
}
</style>
