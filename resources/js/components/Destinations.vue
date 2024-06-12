<!-- resources/js/components/Destinations.vue -->
<template>
  <div>
    <h1>Destinations</h1>
    <p>Vue component loaded successfully.</p>
    <form @submit.prevent="addDestination">
      <input
        v-model="newDestination.name"
        placeholder="Destination Name"
        required
      />
      <input
        v-model="newDestination.latitude"
        placeholder="Latitude"
        required
      />
      <input
        v-model="newDestination.longitude"
        placeholder="Longitude"
        required
      />
      <button type="submit">Add Destination</button>
    </form>
    <ul>
      <li v-for="destination in destinations" :key="destination.id">
        {{ destination.name }} - {{ destination.latitude }},
        {{ destination.longitude }}
        <button @click="removeDestination(destination.id)">Remove</button>
      </li>
    </ul>
    <div id="map" style="height: 500px"></div>
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
    };
  },
  methods: {
    addDestination() {
      console.log("Adding destination:", this.newDestination); // Add this line
      axios
        .post("/destinations", this.newDestination)
        .then((response) => {
          this.destinations.push(response.data);
          this.newDestination = { name: "", latitude: "", longitude: "" };
          this.addMarker(response.data);
        })
        .catch((error) => {
          console.error("Error adding destination:", error); // Add this line
        });
    },
    removeDestination(id) {
      axios.delete(`/destinations/${id}`).then(() => {
        this.destinations = this.destinations.filter(
          (destination) => destination.id !== id
        );
        this.removeMarker(id);
      });
    },
    initializeMap() {
      this.map = L.map("map").setView([0, 0], 2);

      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution:
          '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      }).addTo(this.map);

      this.destinations.forEach((destination) => {
        this.addMarker(destination);
      });
    },
    addMarker(destination) {
      const marker = L.marker([destination.latitude, destination.longitude])
        .addTo(this.map)
        .bindPopup(destination.name);
      marker.id = destination.id;
      if (!this.markers) {
        this.markers = [];
      }
      this.markers.push(marker);
    },
    removeMarker(id) {
      const marker = this.markers.find((m) => m.id === id);
      if (marker) {
        this.map.removeLayer(marker);
        this.markers = this.markers.filter((m) => m.id !== id);
      }
    },
  },
  mounted() {
    axios.get("/destinations").then((response) => {
      this.destinations = response.data;
      this.initializeMap();
    });
  },
};
</script>
