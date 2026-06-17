-- RADTS sample data seed: 6 teachers + 8 resources
-- Safe to run multiple times.
-- Teacher login password for these seeded users: password

START TRANSACTION;

-- 1) Add teacher accounts if they do not already exist (by email)
INSERT INTO users (`NAME`, email, `PASSWORD`, role, created_at)
SELECT 'Peter Mwangi', 'peter.mwangi@radts.local', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'teacher', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'peter.mwangi@radts.local');

INSERT INTO users (`NAME`, email, `PASSWORD`, role, created_at)
SELECT 'Grace Njeri', 'grace.njeri@radts.local', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'teacher', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'grace.njeri@radts.local');

INSERT INTO users (`NAME`, email, `PASSWORD`, role, created_at)
SELECT 'Samuel Kiptoo', 'samuel.kiptoo@radts.local', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'teacher', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'samuel.kiptoo@radts.local');

INSERT INTO users (`NAME`, email, `PASSWORD`, role, created_at)
SELECT 'Lucy Achieng', 'lucy.achieng@radts.local', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'teacher', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'lucy.achieng@radts.local');

INSERT INTO users (`NAME`, email, `PASSWORD`, role, created_at)
SELECT 'John Otieno', 'john.otieno@radts.local', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'teacher', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'john.otieno@radts.local');

INSERT INTO users (`NAME`, email, `PASSWORD`, role, created_at)
SELECT 'Mercy Wambui', 'mercy.wambui@radts.local', '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW', 'teacher', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'mercy.wambui@radts.local');

-- Keep seeded teacher passwords set to: password
UPDATE users SET `PASSWORD` = '$2y$10$.we/QanUs9D2rLf4NfKhi.ar0D8rVuyZV9.yLm.lpOZpNjlmtRqUW' WHERE email IN (
  'peter.mwangi@radts.local',
  'grace.njeri@radts.local',
  'samuel.kiptoo@radts.local',
  'lucy.achieng@radts.local',
  'john.otieno@radts.local',
  'mercy.wambui@radts.local'
) AND role = 'teacher';

-- 2) Add resources if they do not already exist (by NAME)
INSERT INTO resources (`NAME`, `TYPE`, quantity_available)
SELECT 'Mathematics Textbook Grade 7', 'textbook', 18
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE `NAME` = 'Mathematics Textbook Grade 7');

INSERT INTO resources (`NAME`, `TYPE`, quantity_available)
SELECT 'English Grammar Workbook', 'supplementary', 20
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE `NAME` = 'English Grammar Workbook');

INSERT INTO resources (`NAME`, `TYPE`, quantity_available)
SELECT 'Science Practical Guide', 'teaching_guide', 12
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE `NAME` = 'Science Practical Guide');

INSERT INTO resources (`NAME`, `TYPE`, quantity_available)
SELECT 'Kiswahili Fasihi Reader', 'textbook', 16
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE `NAME` = 'Kiswahili Fasihi Reader');

INSERT INTO resources (`NAME`, `TYPE`, quantity_available)
SELECT 'Social Studies Atlas', 'supplementary', 10
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE `NAME` = 'Social Studies Atlas');

INSERT INTO resources (`NAME`, `TYPE`, quantity_available)
SELECT 'CRE Teacher Manual', 'teaching_guide', 8
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE `NAME` = 'CRE Teacher Manual');

INSERT INTO resources (`NAME`, `TYPE`, quantity_available)
SELECT 'Agriculture Activity Book', 'supplementary', 14
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE `NAME` = 'Agriculture Activity Book');

INSERT INTO resources (`NAME`, `TYPE`, quantity_available)
SELECT 'Creative Arts Learner Book', 'textbook', 15
WHERE NOT EXISTS (SELECT 1 FROM resources WHERE `NAME` = 'Creative Arts Learner Book');

COMMIT;
