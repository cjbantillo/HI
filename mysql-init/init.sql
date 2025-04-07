CREATE DATABASE IF NOT EXISTS Lab3;
USE Lab3;

CREATE TABLE tasks (
    taskId SERIAL PRIMARY KEY,
    task VARCHAR(255),
    completed TINYINT(1) DEFAULT 0 -- Use TINYINT(1) for boolean-like behavior
);

INSERT INTO tasks (task, completed)
VALUES ('Buy groceries', 0); -- Use 0 for false and 1 for true