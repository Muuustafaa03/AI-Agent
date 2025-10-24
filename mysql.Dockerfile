FROM mysql:8.0

# Default DB settings (overridden by env vars at runtime)
ENV MYSQL_DATABASE=agentdb \
    MYSQL_USER=agentuser

# Copy initializer SQL (creates schema/tables)
COPY ./sql/init.sql /docker-entrypoint-initdb.d/init.sql
