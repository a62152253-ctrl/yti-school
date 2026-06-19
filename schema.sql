CREATE TABLE IF NOT EXISTS `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` TEXT NOT NULL UNIQUE,
  `email` TEXT NOT NULL UNIQUE,
  `password` TEXT NOT NULL,
  `type` TEXT NOT NULL CHECK(`type` IN ('student', 'teacher')) DEFAULT 'student',
  `class_level` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `notes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `title` TEXT NOT NULL,
  `description` TEXT,
  `subject` TEXT NOT NULL,
  `class_level` TEXT NOT NULL,
  `tags` TEXT DEFAULT NULL,
  `filepath` TEXT NOT NULL,
  `file_type` TEXT NOT NULL CHECK(`file_type` IN ('presentation', 'pdf', 'image')),
  `access_type` TEXT NOT NULL DEFAULT 'free' CHECK(`access_type` IN ('free', 'premium')),
  `premium_price` REAL DEFAULT 0,
  `views` INTEGER DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `my_lessons` (
  `user_id` INTEGER NOT NULL,
  `note_id` INTEGER NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `note_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `reports` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `note_id` INTEGER NOT NULL,
  `user_id` INTEGER NOT NULL,
  `reason` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `history` (
  `user_id` INTEGER NOT NULL,
  `note_id` INTEGER NOT NULL,
  `watched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `note_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `comments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `note_id` INTEGER NOT NULL,
  `user_id` INTEGER NOT NULL,
  `content` TEXT NOT NULL,
  `parent_id` INTEGER DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `playlists` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `title` TEXT NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `playlist_notes` (
  `playlist_id` INTEGER NOT NULL,
  `note_id` INTEGER NOT NULL,
  `position` INTEGER DEFAULT 0,
  PRIMARY KEY (`playlist_id`, `note_id`),
  FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `subscriptions` (
  `student_id` INTEGER NOT NULL,
  `teacher_id` INTEGER NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`, `teacher_id`),
  FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `likes` (
  `user_id` INTEGER NOT NULL,
  `note_id` INTEGER NOT NULL,
  `type` TEXT NOT NULL CHECK(`type` IN ('like', 'dislike')),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `note_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `watch_later` (
  `user_id` INTEGER NOT NULL,
  `note_id` INTEGER NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `note_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `purchases` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `note_id` INTEGER NOT NULL,
  `amount` REAL NOT NULL,
  `stripe_id` TEXT UNIQUE DEFAULT NULL,
  `payment_status` TEXT DEFAULT 'pending' CHECK(payment_status IN ('pending', 'completed', 'failed')),
  `paid_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(user_id, note_id),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `cart_items` (
  `user_id` INTEGER NOT NULL,
  `note_id` INTEGER NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `note_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `title` TEXT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` INTEGER DEFAULT 0,
  `link` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `token` TEXT NOT NULL UNIQUE,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `personal_notes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NOT NULL,
  `note_id` INTEGER NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`note_id`) REFERENCES `notes`(`id`) ON DELETE CASCADE,
  UNIQUE(user_id, note_id)
);
