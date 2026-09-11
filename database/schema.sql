-- Create Database
CREATE DATABASE IF NOT EXISTS ustp_tabulation;
USE ustp_tabulation;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'tabulator', 'judge', 'viewer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Events Table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    description TEXT,
    status ENUM('draft', 'live', 'archived') DEFAULT 'draft',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    computation_type ENUM('raw_average', 'rank_based', 'weighted_criteria') NOT NULL,
    tiebreak_rule ENUM('highest_criterion', 'sum_criteria', 'more_top_ranks', 'lowest_variance', 'manual') DEFAULT 'manual',
    tiebreak_criterion_id INT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Criteria Table
CREATE TABLE criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    weight_percent DECIMAL(5,2) NOT NULL,
    min_score DECIMAL(5,2) DEFAULT 0,
    max_score DECIMAL(5,2) DEFAULT 100,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Contestants Table
CREATE TABLE contestants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('solo', 'team') DEFAULT 'solo',
    scoring_status ENUM('not_started', 'in_progress', 'complete', 'locked') DEFAULT 'not_started',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Judge Assignments Table
CREATE TABLE judge_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (user_id, category_id)
);

-- Scores Table
CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judge_assignment_id INT NOT NULL,
    contestant_id INT NOT NULL,
    criteria_id INT NOT NULL,
    score_value DECIMAL(5,2) NOT NULL,
    is_locked BOOLEAN DEFAULT FALSE,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unlocked_by INT NULL,
    FOREIGN KEY (judge_assignment_id) REFERENCES judge_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
    FOREIGN KEY (criteria_id) REFERENCES criteria(id) ON DELETE CASCADE,
    FOREIGN KEY (unlocked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_score (judge_assignment_id, contestant_id, criteria_id)
);

-- Results Table
CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    contestant_id INT NOT NULL,
    final_score DECIMAL(10,2) NOT NULL,
    final_rank INT NOT NULL,
    is_released BOOLEAN DEFAULT FALSE,
    released_by INT NULL,
    released_at TIMESTAMP NULL,
    computed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
    FOREIGN KEY (released_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_contestant_category (category_id, contestant_id)
);

-- Reusable scoring-rubric library
CREATE TABLE rubric_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    template_category VARCHAR(100) NULL,
    description TEXT NULL,
    computation_type ENUM('raw_average', 'rank_based', 'weighted_criteria') NOT NULL DEFAULT 'weighted_criteria',
    tiebreak_rule ENUM('highest_criterion', 'sum_criteria', 'more_top_ranks', 'lowest_variance', 'manual') NOT NULL DEFAULT 'manual',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE rubric_template_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    weight_percent DECIMAL(5,2) NOT NULL,
    min_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    max_score DECIMAL(5,2) NOT NULL DEFAULT 100,
    FOREIGN KEY (template_id) REFERENCES rubric_templates(id) ON DELETE CASCADE
);

-- Tabulation audit tables
CREATE TABLE tabulation_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    computation_type ENUM('raw_average', 'rank_based', 'weighted_criteria') NOT NULL,
    score_count INT NOT NULL,
    triggered_by INT NULL,
    computed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE tabulation_run_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    run_id INT NOT NULL,
    score_id INT NULL,
    judge_assignment_id INT NOT NULL,
    contestant_id INT NOT NULL,
    criteria_id INT NOT NULL,
    score_value DECIMAL(5,2) NOT NULL,
    submitted_at TIMESTAMP NULL,
    FOREIGN KEY (run_id) REFERENCES tabulation_runs(id) ON DELETE CASCADE
);

-- Seed default admin (password: 'password')
INSERT INTO users (full_name, email, password_hash, role) VALUES 
('Admin User', 'admin@ustp.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
