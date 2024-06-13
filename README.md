# Road Trip Planner

## Overview

Road Trip Planner is a web application that allows users to list, order, and visualize their desired travel destinations on a map. It calculates the best route between destinations and provides information about distances and journey times.

## Features

- Add destinations via a form or by clicking on a map.
- Rearrange the order of destinations via drag-and-drop.
- Display destinations on a map.
- Calculate and display the distance and journey time between destinations.
- Save journey data to local storage for persistence across sessions.
- Additional functionality includes suggesting points of interest and shortstops (e.g., hotels, attractions).

## Technologies Used

- Vue.js
- Laravel (Backend API)
- Leaflet (Map rendering)
- Axios (HTTP requests)
- OpenRouteService (Distance and time calculation)
- vue-draggable-next (Drag-and-drop functionality)

## Setup and Installation

### Prerequisites

- Node.js and npm
- Composer
- PHP
- Laravel

### Backend Setup

1. Clone the repository:
    ```sh
    git clone https://github.com/JonathanAudu/road-trip-planner.git
    ```

2. Navigate to the backend directory:
    ```sh
    cd road-trip-planner/backend
    ```

3. Install dependencies:
    ```sh
    composer install
    ```

4. Copy the `.env` file and configure your database and other settings:
    ```sh
    cp .env.example .env
    php artisan key:generate
    ```

5. Run the migrations:
    ```sh
    php artisan migrate
    ```

6. Start the Laravel server:
    ```sh
    php artisan serve
    ```

### Frontend Setup

1. Navigate to the frontend directory:
    ```sh
    cd ../frontend
    ```

2. Install dependencies:
    ```sh
    npm install
    ```

3. Start the development server:
    ```sh
    npm run dev
    ```

## API Endpoints

- `GET /destinations` - Fetch all destinations
- `POST /destinations` - Add a new destination
- `DELETE /destinations/{id}` - Delete a destination

## Usage

1. Open the application in your browser (usually at `http://localhost:3000` for the frontend and `http://localhost:8000` for the backend).
2. Add destinations using the form or by clicking on the map.
3. Rearrange the order of destinations by dragging and dropping them.
4. View the destinations on the map and check the journey details.

## Configuration

### OpenRouteService API

To enable distance and journey time calculations, you need an API key from OpenRouteService.

1. Sign up for an API key at [OpenRouteService](https://openrouteservice.org/sign-up/).
2. In the `Destinations.vue` file, replace `YOUR_OPENROUTESERVICE_API_KEY` with your actual API key.

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request with your changes.

## Acknowledgements

- [Vue.js](https://vuejs.org/)
- [Laravel](https://laravel.com/)
- [Leaflet](https://leafletjs.com/)
- [OpenRouteService](https://openrouteservice.org/)
- [vue-draggable-next](https://github.com/anish2690/vue-draggable-next)

## Contact

For questions or feedback, please reach out to [jaudu2@gmail.com](mailto:jaudu2@gmail.com).
