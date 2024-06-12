<template>
  <div>
    <h1>Destinations</h1>
    <form @submit.prevent="addDestination">
      <input v-model="newDestination.name" placeholder="Destination Name" required />
      <input v-model="newDestination.latitude" placeholder="Latitude" required />
      <input v-model="newDestination.longitude" placeholder="Longitude" required />
      <button type="submit">Add Destination</button>
    </form>
    <draggable v-model="destinations" @end="updateOrder">
      <transition-group>
        <div v-for="(destination, index) in destinations" :key="destination.id">
          {{ index + 1 }}. {{ destination.name }} - {{ destination.latitude }},
          {{ destination.longitude }}
          <button @click="removeDestination(destination.id)">Remove</button>
        </div>
      </transition-group>
    </draggable>
    <div class="map-container">
      <div id="map" class="map"></div>
    </div>
    <div v-if="journeyData">
      <h2>Journey Summary</h2>
      <p>Total Distance: {{ journeyData.totalDistance }} km</p>
      <p>Total Time: {{ journeyData.totalTime }} minutes</p>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import L from "leaflet";
import { VueDraggableNext } from "vue-draggable-next";

export default {
  components: { VueDraggableNext },
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
      journeyData: null,
    };
  },
  methods: {
    addDestination() {
      axios
        .post("/destinations", this.newDestination)
        .then((response) => {
          const destination = response.data;
          this.destinations.push(destination);
          this.addMarker(destination);
          this.newDestination = { name: "", latitude: "", longitude: "" };
          this.saveToLocalStorage();
          this.calculateJourneyData();
        })
        .catch((error) => {
          console.error("Error adding destination:", error);
        });
    },
    removeDestination(id) {
      axios
        .delete(`/destinations/${id}`)
        .then(() => {
          this.destinations = this.destinations.filter(
            (destination) => destination.id !== id
          );
          this.removeMarker(id);
          this.saveToLocalStorage();
          this.calculateJourneyData();
        })
        .catch((error) => {
          console.error("Error removing destination:", error);
        });
    },
    updateOrder() {
      this.saveToLocalStorage();
      this.calculateJourneyData();
      this.updateMapLines();
    },
    initializeMap() {
      try {
        this.map = L.map("map").setView([0, 0], 2);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
          attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(this.map);

        this.map.on("click", this.onMapClick);
      } catch (error) {
        console.error("Error initializing map:", error);
      }
    },
    async onMapClick(e) {
      const { lat, lng } = e.latlng;
      this.newDestination.latitude = lat;
      this.newDestination.longitude = lng;

      try {
        const response = await axios.get(
          `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`
        );
        const name = response.data.display_name;
        this.newDestination.name = name;
      } catch (error) {
        console.error("Error fetching location name:", error);
        this.newDestination.name = "Unknown location";
      }
    },
    addMarker(destination) {
      try {
        const marker = L.marker([destination.latitude, destination.longitude])
          .addTo(this.map)
          .bindPopup(destination.name);
        marker.id = destination.id;
        this.markers.push(marker);
      } catch (error) {
        console.error("Error adding marker:", error);
      }
    },
    removeMarker(id) {
      try {
        const marker = this.markers.find((m) => m.id === id);
        if (marker) {
          this.map.removeLayer(marker);
          this.markers = this.markers.filter((m) => m.id !== id);
        }
      } catch (error) {
        console.error("Error removing marker:", error);
      }
    },
    updateMapLines() {
      try {
        if (this.polyline) {
          this.map.removeLayer(this.polyline);
        }

        const latlngs = this.destinations.map((destination) => [
          destination.latitude,
          destination.longitude,
        ]);
        this.polyline = L.polyline(latlngs, { color: "blue" }).addTo(this.map);

        if (latlngs.length > 0) {
          this.map.fitBounds(L.latLngBounds(latlngs));
        }
      } catch (error) {
        console.error("Error updating map lines:", error);
      }
    },
    calculateJourneyData() {
      if (this.destinations.length < 2) {
        this.journeyData = null;
        return;
      }

      const origin = this.destinations[0];
      const destinations = this.destinations.slice(1);

      const waypoints = destinations.map((destination) => {
        return {
          location: {
            lat: destination.latitude,
            lng: destination.longitude,
          },
        };
      });

      const params = {
        profile: "driving",
        coordinates: [
          [origin.longitude, origin.latitude],
          ...waypoints.map((waypoint) => [waypoint.location.lng, waypoint.location.lat]),
        ],
      };

      axios
        .get("https://api.openrouteservice.org/v2/directions/driving-car", {
          params,
          headers: {
            Authorization: "5b3ce3597851110001cf6248f8945eee55ae49e09a85ec7ed61d674c",
          },
        })
        .then((response) => {
          const route = response.data.routes[0];
          const totalDistance = (route.summary.distance / 1000).toFixed(2); // in km
          const totalTime = (route.summary.duration / 60).toFixed(2); // in minutes

          this.journeyData = {
            totalDistance,
            totalTime,
          };
        })
        .catch((error) => {
          console.error("Error fetching route data:", error);
        });
    },
    saveToLocalStorage() {
      localStorage.setItem("destinations", JSON.stringify(this.destinations));
    },
    loadFromLocalStorage() {
      const storedDestinations = JSON.parse(localStorage.getItem("destinations"));
      if (storedDestinations) {
        this.destinations = storedDestinations;
        this.destinations.forEach((destination) => this.addMarker(destination));
        this.calculateJourneyData();
        this.updateMapLines();
      }
    },
  },
  mounted() {
    this.initializeMap();
    axios
      .get("/destinations")
      .then((response) => {
        this.destinations = response.data;
        this.destinations.forEach((destination) => this.addMarker(destination));
        this.calculateJourneyData();
        this.updateMapLines();
      })
      .catch((error) => {
        console.error("Error fetching destinations:", error);
      });
    this.loadFromLocalStorage();
  },
};
</script>

<style scoped>
.map-container {
  width: 100%;
  max-width: 800px;
  height: 500px;
  overflow: hidden;
  position: relative;
}

.map {
  width: 100%;
  height: 100%;
  position: absolute;
}
</style>
