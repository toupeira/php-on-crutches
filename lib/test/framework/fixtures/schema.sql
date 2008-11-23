CREATE TABLE `users` (
  `id` INTEGER PRIMARY KEY,
  `created_at` DATETIME,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `crypted_password` CHAR(40) NOT NULL,
  `salt` CHAR(40) NOT NULL,
  `admin` BOOL NOT NULL DEFAULT 0
);
