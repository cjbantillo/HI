CREATE DATABASE IF NOT EXISTS Lab3;
USE Lab3;

CREATE TABLE tasks (
    taskId SERIAL PRIMARY KEY,
    task VARCHAR(255),
    completed VARCHAR(255)
);

INSERT INTO tasks (task, completed)
VALUES ('Buy groceries', '0');