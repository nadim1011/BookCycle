DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS borrow_requests;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    university VARCHAR(150) DEFAULT NULL,
    department VARCHAR(150) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    contact_pref ENUM('email','phone','both','none') DEFAULT 'email',
    role ENUM('student','admin') NOT NULL DEFAULT 'student',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE books (
    id INT NOT NULL AUTO_INCREMENT,
    owner_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    isbn VARCHAR(32) DEFAULT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    condition_status ENUM('new','almost_new','good','usable') NOT NULL DEFAULT 'good',
    cover_image VARCHAR(255) DEFAULT NULL,
    university VARCHAR(150) DEFAULT NULL,
    pickup_instructions TEXT DEFAULT NULL,
    availability_status ENUM('available','reserved','borrowed','inactive') NOT NULL DEFAULT 'available',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_books_owner (owner_id),
    KEY idx_books_status (availability_status),
    KEY idx_books_active (is_active),
    CONSTRAINT fk_books_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE borrow_requests (
    id INT NOT NULL AUTO_INCREMENT,
    book_id INT NOT NULL,
    requester_id INT NOT NULL,
    owner_id INT NOT NULL,
    start_date DATE NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Pending','Approved','Rejected','Borrowed','Return Requested','Returned','Cancelled','Overdue') NOT NULL DEFAULT 'Pending',
    message TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_borrow_book (book_id),
    KEY idx_borrow_requester (requester_id),
    KEY idx_borrow_owner (owner_id),
    KEY idx_borrow_status (status),
    CONSTRAINT fk_borrow_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_borrow_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_borrow_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loans (
    id INT NOT NULL AUTO_INCREMENT,
    request_id INT NOT NULL,
    book_id INT NOT NULL,
    owner_id INT NOT NULL,
    borrower_id INT NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('active','returned','overdue','cancelled') NOT NULL DEFAULT 'active',
    approved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    returned_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_loans_request (request_id),
    KEY idx_loans_book (book_id),
    KEY idx_loans_owner (owner_id),
    KEY idx_loans_borrower (borrower_id),
    KEY idx_loans_status (status),
    CONSTRAINT fk_loans_request FOREIGN KEY (request_id) REFERENCES borrow_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_loans_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_loans_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_loans_borrower FOREIGN KEY (borrower_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    related_type VARCHAR(50) DEFAULT NULL,
    related_id INT DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user (user_id),
    KEY idx_notifications_read (is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
    id INT NOT NULL AUTO_INCREMENT,
    loan_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reviews_loan (loan_id),
    CONSTRAINT fk_reviews_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewee FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reports (
    id INT NOT NULL AUTO_INCREMENT,
    reporter_id INT DEFAULT NULL,
    reported_user_id INT DEFAULT NULL,
    reported_book_id INT DEFAULT NULL,
    reason VARCHAR(200) NOT NULL,
    details TEXT DEFAULT NULL,
    status ENUM('open','reviewed','resolved','rejected') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reports_reporter (reporter_id),
    KEY idx_reports_user (reported_user_id),
    KEY idx_reports_book (reported_book_id),
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_user FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_book FOREIGN KEY (reported_book_id) REFERENCES books(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_logs (
    id INT NOT NULL AUTO_INCREMENT,
    admin_id INT DEFAULT NULL,
    action VARCHAR(200) NOT NULL,
    details TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admin_logs_admin (admin_id),
    CONSTRAINT fk_admin_logs_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (name, email, password_hash, university, department, role, is_active) VALUES
('অ্যাডমিন', 'admin@bookcycle.local', '$2b$12$E3hLJ/1FqR.8kcSK7wp4fOC1QLpUMv/PpVHrXyzVTmz.5f0OgxMGi', 'ঢাকা বিশ্ববিদ্যালয়', 'প্রশাসন', 'admin', 1),
('সাব্বির আহমেদ', 'sabbir@student.local', '$2b$12$WEBa5z8tWzk4berFgrmtK.YIIw2e.9xnV61g.8ktmzncfvcFEUFN.', 'ঢাকা বিশ্ববিদ্যালয়', 'কম্পিউটার বিজ্ঞান', 'student', 1),
('নুসরাত আক্তার', 'nusrat@student.local', '$2b$12$WEBa5z8tWzk4berFgrmtK.YIIw2e.9xnV61g.8ktmzncfvcFEUFN.', 'জাহাঙ্গীরনগর বিশ্ববিদ্যালয়', 'গণিত', 'student', 1);

INSERT INTO books (owner_id, title, author, isbn, category, description, condition_status, cover_image, university, pickup_instructions, availability_status, is_active) VALUES
(2, 'অ্যালগরিদম গাইড', 'পল ডারগ', '9780132350884', 'কম্পিউটার বিজ্ঞান', 'প্রোগ্রামিং ও অ্যালগরিদম শেখার জন্য ব্যবহারিক বই।', 'good', NULL, 'ঢাকা বিশ্ববিদ্যালয়', 'সার্কিট হাউসের সামনে', 'available', 1),
(3, 'ডিফারেনশিয়াল ইকুয়েশন', 'পল টমাস', '9780078022159', 'গণিত', 'সারাবিশ্বের গণিত শিক্ষার্থীদের জন্য গুরুত্বপূর্ণ বই।', 'almost_new', NULL, 'জাহাঙ্গীরনগর বিশ্ববিদ্যালয়', 'লাইব্রেরি সম্মুখে', 'available', 1);

INSERT INTO borrow_requests (book_id, requester_id, owner_id, start_date, due_date, status, message) VALUES
(1, 3, 2, '2026-10-10', '2026-10-20', 'Pending', 'ব্যবহারের জন্য অনুরোধ পাঠানো হচ্ছে।');

INSERT INTO notifications (user_id, type, message, related_type, related_id) VALUES
(2, 'request', 'নতুন বই ধার নেওয়ার অনুরোধ এসেছে।', 'borrow_request', 1),
(3, 'approved', 'আপনার অনুরোধটি গ্রহণ করা হয়েছে।', 'borrow_request', 1);
