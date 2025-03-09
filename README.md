
## Installation

1. Clone the repository:
    ```bash
    git clone https://github.com/Tohid-Hemmati/Insider_test.git
    cd src
    ```

2. Set up environment variables:
    ```bash
    cp .env.example .env
    ```
---

## Docker Setup

1. Build and start Docker containers:
    ```bash
    docker-compose up --build
    ```

2. Verify containers are running:
    ```bash
    docker ps
    ```

3. Access the app at [http://localhost:8000](http://localhost:8000).

---

## Project Structure

- `docker/`: Docker configurations for PHP, Nginx, and MySQL.
- `src/`: Laravel application source code.
- `docker/php/Dockerfile`: PHP application container definition.
- `docker/nginx/default.conf`: Nginx configuration.
- `src/resources/js`: Vue application source code.
---

## Running Tests:
in order to run tests, run the following command:
```bash
 docker exec -it football-league-app bash
```
then inside the container, run the following command:
```bash
 php artisan test --testsuite=Feature --filter=LeagueTest
```
