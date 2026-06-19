INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 1A', '1001', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1001');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 1B', '1002', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1002');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 2A', '1003', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1003');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 2B', '1004', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1004');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 3A', '1005', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1005');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 3B', '1006', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1006');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 4A', '1007', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1007');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 4B', '1008', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1008');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 5A', '1009', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1009');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 5B', '1010', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1010');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 6A', '1011', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1011');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 6B', '1012', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1012');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 7A', '1013', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1013');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 7B', '1014', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1014');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 8A', '1015', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1015');

INSERT INTO users (NAME, email, PASSWORD, role, created_at)
SELECT 'Student Leader Grade 8B', '1016', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'student_leader', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = '1016');

UPDATE users SET NAME='Student Leader Grade 1A', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1001';
UPDATE users SET NAME='Student Leader Grade 1B', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1002';
UPDATE users SET NAME='Student Leader Grade 2A', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1003';
UPDATE users SET NAME='Student Leader Grade 2B', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1004';
UPDATE users SET NAME='Student Leader Grade 3A', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1005';
UPDATE users SET NAME='Student Leader Grade 3B', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1006';
UPDATE users SET NAME='Student Leader Grade 4A', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1007';
UPDATE users SET NAME='Student Leader Grade 4B', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1008';
UPDATE users SET NAME='Student Leader Grade 5A', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1009';
UPDATE users SET NAME='Student Leader Grade 5B', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1010';
UPDATE users SET NAME='Student Leader Grade 6A', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1011';
UPDATE users SET NAME='Student Leader Grade 6B', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1012';
UPDATE users SET NAME='Student Leader Grade 7A', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1013';
UPDATE users SET NAME='Student Leader Grade 7B', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1014';
UPDATE users SET NAME='Student Leader Grade 8A', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1015';
UPDATE users SET NAME='Student Leader Grade 8B', role='student_leader', PASSWORD='$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email='1016';
