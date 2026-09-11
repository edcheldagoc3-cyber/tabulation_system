-- Add tabulation audit history without changing existing application data.
USE ustp_tabulation;

CREATE TABLE IF NOT EXISTS tabulation_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    computation_type ENUM('raw_average', 'rank_based', 'weighted_criteria') NOT NULL,
    score_count INT NOT NULL,
    triggered_by INT NULL,
    computed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tabulation_run_scores (
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
