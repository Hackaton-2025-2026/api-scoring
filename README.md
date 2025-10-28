# API Scoring

This API is designed to manage race scoring, including races, runners, and their results. It features a cron job that automatically updates race times and ranks for ongoing races every minute. It also provides real-time updates via Mercure.

## Prerequisites

Before you begin, ensure you have the following installed on your system:

*   **Docker**: [Install Docker](https://docs.docker.com/get-docker/)
*   **Docker Compose**: [Install Docker Compose](https://docs.docker.com/compose/install/) (usually comes with Docker Desktop)

## Getting Started

Follow these steps to set up and run the API locally.

### 1. Clone the Repository (if applicable)

```bash
git clone <repository-url>
cd api-scoring
```

### 2. Environment Configuration

Create a `.env` file in the root of the project by copying the `.env.example` (if it existed, otherwise create it manually).

```bash
cp .env.example .env # If .env.example exists
# OR create .env manually
```

Ensure the following variables are correctly configured in your `.env` file:

*   `APP_SECRET`: A unique secret key for your Symfony application. You can generate one using `php -r 'echo bin2hex(random_bytes(16));'`.
*   `DATABASE_URL`: The connection string for your database. It should match the credentials defined in `docker-compose.yml`.
*   `API_PUBLIC_URL`: The public URL of your API, used for Mercure topic URLs. For local development with ngrok, this would be your ngrok URL.

    Example `.env` entry:
    ```
    APP_ENV=dev
    APP_SECRET=your_generated_app_secret_here
    DATABASE_URL="mysql://app_user:app_password@mysql:3306/app_db?serverVersion=8.0.32&charset=utf8mb4"
    API_PUBLIC_URL="https://unsurrounded-gary-unprotruding.ngrok-free.dev"
    ```

### 3. Build and Start Docker Containers

Use Docker Compose to build the images and start the services.

```bash
docker-compose up --build -d
```

This command will:
*   Build the `web` service image (based on `Dockerfile`).
*   Start the `web` (Symfony application), `mysql` (database), `mercure` (real-time updates), and `phpmyadmin` (database management UI) services.
*   Run services in detached mode (`-d`).

### 4. Initialize Database and Load Fixtures

Once the containers are running, you need to create the database schema and load initial data.

```bash
docker exec -it web php bin/console doctrine:schema:update --force --no-interaction
docker exec -it web php bin/console doctrine:fixtures:load --no-interaction
```

Alternatively, you can use the `Makefile` targets:

```bash
make fixtures
```

### 5. Access the API

The API will be accessible at `http://localhost:8000`.

*   **API Documentation (NelmioApiDocBundle)**: `http://localhost:8000/api/doc`
*   **PhpMyAdmin**: `http://localhost:8080` (Login with `root` and `root_password` or `app_user` and `app_password`)

### 6. Real-time Updates with Mercure

The API publishes real-time updates for race changes via Mercure.

*   **Mercure Hub**: The Mercure hub is accessible at `http://localhost:3000`.
*   **Subscribing from Frontend**: Your frontend application (e.g., Vue.js) can subscribe to updates using the `EventSource` API. The topic URL for a specific race will be `YOUR_API_PUBLIC_URL/races/{raceId}`.

    Example (conceptual) frontend subscription:
    ```javascript
    const mercureUrl = new URL('http://localhost:3000/.well-known/mercure');
    mercureUrl.searchParams.append('topic', `https://unsurrounded-gary-unprotruding.ngrok-free.dev/races/1`); // Replace with actual race ID

    const eventSource = new EventSource(mercureUrl);
    eventSource.onmessage = (event) => {
      console.log('Received update:', JSON.parse(event.data));
      // Update your UI here
    };
    ```

### 7. Cron Job for Data Updates

A cron job is configured to run every minute inside the `web` container. It executes the `app:update-race-times` command, which updates the `time` and `runnerRank` for ongoing races, and publishes these changes to Mercure.

You can view the cron job logs to monitor its execution:

```bash
docker exec -it web cat /var/log/cron.log
```

Alternatively, using the `Makefile`:

```bash
make logs
```

## Makefile Commands

For convenience, a `Makefile` is provided with common commands:

*   `make build`: Builds the Docker images.
*   `make start`: Starts the Docker containers.
*   `make stop`: Stops and removes the Docker containers.
*   `make restart`: Stops, rebuilds, and starts the Docker containers.
*   `make fixtures`: Updates the database schema and loads data fixtures.
*   `make logs`: Displays the cron job logs.
*   `make shell`: Provides a bash shell inside the `web` container.
