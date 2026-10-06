-- Mindful premium wellness app — MySQL 8+ / MariaDB
CREATE DATABASE IF NOT EXISTS mindful_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mindful_db;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  subscription_plan ENUM('free','plus','premium') NOT NULL DEFAULT 'free',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_email (email),
  INDEX idx_users_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS therapists (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  specialty VARCHAR(150) NOT NULL,
  experience_years TINYINT UNSIGNED NOT NULL DEFAULT 3,
  rating DECIMAL(2,1) NOT NULL DEFAULT 4.8,
  photo VARCHAR(255) DEFAULT NULL,
  session_types VARCHAR(150) NOT NULL DEFAULT 'Video, Audio',
  bio TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_therapists_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS therapist_availability (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  therapist_id INT UNSIGNED NOT NULL,
  day_of_week TINYINT NOT NULL COMMENT '0=Sun..6=Sat',
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  CONSTRAINT fk_avail_therapist FOREIGN KEY (therapist_id) REFERENCES therapists(id) ON DELETE CASCADE,
  INDEX idx_avail_therapist_day (therapist_id, day_of_week)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  therapist_id INT UNSIGNED NOT NULL,
  appt_date DATE NOT NULL,
  appt_time TIME NOT NULL,
  status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  notes VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_appt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_appt_therapist FOREIGN KEY (therapist_id) REFERENCES therapists(id) ON DELETE CASCADE,
  INDEX idx_appt_user_date (user_id, appt_date),
  INDEX idx_appt_therapist_slot (therapist_id, appt_date, appt_time)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS moods (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(30) NOT NULL UNIQUE,
  score TINYINT UNSIGNED NOT NULL,
  emoji VARCHAR(12) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mood_tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS check_ins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  mood_id TINYINT UNSIGNED NOT NULL,
  note VARCHAR(280) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_checkin_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_checkin_mood FOREIGN KEY (mood_id) REFERENCES moods(id),
  INDEX idx_checkin_user_created (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mood_entry_tags (
  check_in_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (check_in_id, tag_id),
  CONSTRAINT fk_met_checkin FOREIGN KEY (check_in_id) REFERENCES check_ins(id) ON DELETE CASCADE,
  CONSTRAINT fk_met_tag FOREIGN KEY (tag_id) REFERENCES mood_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS journal_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  body TEXT NOT NULL,
  mood_id TINYINT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_journal_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_journal_mood FOREIGN KEY (mood_id) REFERENCES moods(id) ON DELETE SET NULL,
  FULLTEXT INDEX ft_journal (title, body),
  INDEX idx_journal_user_created (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(60) NOT NULL,
  title VARCHAR(160) NOT NULL,
  description VARCHAR(280) NOT NULL,
  body TEXT,
  image VARCHAR(255) DEFAULT NULL,
  reading_minutes TINYINT UNSIGNED NOT NULL DEFAULT 5,
  is_premium TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_resources_category (category)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resource_bookmarks (
  user_id INT UNSIGNED NOT NULL,
  resource_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, resource_id),
  CONSTRAINT fk_rb_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rb_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wellness_activities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('breathing','gratitude','grounding','reflection') NOT NULL,
  minutes TINYINT UNSIGNED DEFAULT NULL,
  content VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_wa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_wa_user_type (user_id, type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  body VARCHAR(280) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user_read (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS achievements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  title VARCHAR(100) NOT NULL,
  description VARCHAR(200) NOT NULL,
  icon VARCHAR(12) NOT NULL DEFAULT '🌱'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_achievements (
  user_id INT UNSIGNED NOT NULL,
  achievement_id INT UNSIGNED NOT NULL,
  unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, achievement_id),
  CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ua_ach FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  plan ENUM('free','plus','premium') NOT NULL DEFAULT 'free',
  status ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS crisis_resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country VARCHAR(100) NOT NULL,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(60) DEFAULT NULL,
  website VARCHAR(200) DEFAULT NULL,
  availability VARCHAR(100) NOT NULL DEFAULT '24/7',
  description VARCHAR(280) DEFAULT NULL,
  INDEX idx_crisis_country (country)
) ENGINE=InnoDB;

-- Seed data
INSERT INTO moods (label, score, emoji) VALUES
('Great',5,'😊'),('Good',4,'🙂'),('Okay',3,'😐'),('Low',2,'😔'),('Very Low',1,'😢')
ON DUPLICATE KEY UPDATE label=VALUES(label);

INSERT INTO mood_tags (name) VALUES ('Work'),('Sleep'),('Family'),('Exercise'),('Gratitude')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO achievements (code, title, description, icon) VALUES
('first-checkin','First Check-in','Completed your first mood check-in','🌱'),
('week-reflection','7 Day Reflection','Checked in 7 days in a row','🌿'),
('thirty-checkins','30 Check-ins','Completed 30 mood check-ins','✨'),
('ten-sessions','10 Wellness Sessions','Finished 10 wellness activities','🧘'),
('ten-reads','10 Resources Read','Bookmarked 10 resources','📖')
ON DUPLICATE KEY UPDATE title=VALUES(title);

INSERT INTO therapists (name, specialty, experience_years, rating, session_types, bio) VALUES
('Dr. Maya Santos','Anxiety & Stress',8,4.9,'Video, Audio','Warm, practical support for stress, overthinking, and burnout.'),
('James Carter, LMFT','Relationships',6,4.8,'Video','Helps couples and individuals build calmer communication.'),
('Dr. Priya Nair','Sleep & Mindfulness',10,5.0,'Video, Chat','Specialist in sleep habits, mindfulness, and evening routines.')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO resources (category, title, description, body, reading_minutes, is_premium) VALUES
('Stress','A 5-minute reset for busy days','A short breathing and grounding routine to lower tension.','Practice slow breathing, relax your shoulders, and name three things you can see. Repeat for five calm minutes.',5,0),
('Sleep','Wind down for deeper rest','A gentle evening routine for better sleep.','Dim lights, no screens for 30 minutes, and three slow breaths before bed.',6,0),
('Mindfulness','Notice one moment fully','A one-minute mindfulness pause you can do anywhere.','Pause, feel your feet on the floor, and follow three breaths without changing them.',4,0),
('Anxiety','Untangle worried thoughts','Write worries down, then sort what you can act on.','List worries, circle one small action, and let the rest wait until tomorrow.',7,1)
ON DUPLICATE KEY UPDATE title=VALUES(title);

INSERT INTO crisis_resources (country, name, phone, website, availability, description) VALUES
('Global','Find a Helpline International','—','https://findahelpline.org','Varies','Directory of helplines by country.'),
('USA','988 Suicide and Crisis Lifeline','988','https://988lifeline.org','24/7','Call or text 988 for support in the US.'),
('UK & Ireland','Samaritans','116 123','https://www.samaritans.org','24/7','Free listening support, day or night.')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Default admin (password: Admin123! — change after first login)
-- Hash generated with bcrypt cost 10 for 'Admin123!'
INSERT INTO users (name, email, password_hash, role, subscription_plan) VALUES
('Admin','admin@mindful.local','$2b$10$4KhYtrnoczsRWkvRtydCq.sYyvmCqPikrN4QmOMW57IWmz.KwIE9q','admin','premium')
ON DUPLICATE KEY UPDATE name=VALUES(name);
