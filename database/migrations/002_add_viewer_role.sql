-- Allow authenticated read-only viewer accounts while retaining public viewing.
USE ustp_tabulation;

ALTER TABLE users
MODIFY role ENUM('admin', 'tabulator', 'judge', 'viewer') NOT NULL;
