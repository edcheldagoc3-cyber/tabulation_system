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
