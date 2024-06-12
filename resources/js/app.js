import { createApp } from 'vue';
import Destinations from './components/Destinations.vue';

const app = createApp({});
app.component('destinations', Destinations);
app.mount('#app');
