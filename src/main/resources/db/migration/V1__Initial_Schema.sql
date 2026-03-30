-- Initial Database Schema for PostgreSQL

-- Create compagnies table
CREATE TABLE IF NOT EXISTS compagnies (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id           SERIAL PRIMARY KEY,
    username     VARCHAR(255) UNIQUE NOT NULL,
    email        VARCHAR(255) UNIQUE NOT NULL,
    password     VARCHAR(255) NOT NULL,
    telephone    VARCHAR(20),
    compagnie_id INT,
    enabled      BOOLEAN DEFAULT TRUE,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (compagnie_id) REFERENCES compagnies(id) ON DELETE SET NULL
);

-- Create authority definitions table
CREATE TABLE IF NOT EXISTS authority_definitions (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Create authorities table
CREATE TABLE IF NOT EXISTS authorities (
    id        SERIAL PRIMARY KEY,
    user_id   INT NOT NULL,
    authority VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, authority)
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_users_username    ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_email       ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_compagnie   ON users(compagnie_id);
CREATE INDEX IF NOT EXISTS idx_authorities_user  ON authorities(user_id);
