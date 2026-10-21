-- โครงสร้างฐานข้อมูล ระบบจองห้องประชุม / แจ้งซ่อม / ลงเวลา
-- วันที่เก็บเป็น ISO (ค.ศ.) แปลงเป็น พ.ศ. ตอนแสดงผลเท่านั้น

CREATE TABLE IF NOT EXISTS departments (
    id          VARCHAR(10)  NOT NULL PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    code        VARCHAR(20)  NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id              VARCHAR(10)  NOT NULL PRIMARY KEY,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    name            VARCHAR(150) NOT NULL,
    role            ENUM('Administrator','Officer','Technician','Executive','Employee') NOT NULL DEFAULT 'Employee',
    department_id   VARCHAR(10)  NULL,
    position        VARCHAR(150) NULL,
    phone           VARCHAR(30)  NULL,
    skill           VARCHAR(100) NULL,
    emergency_contact VARCHAR(255) NULL,
    address         VARCHAR(255) NULL,
    avatar          VARCHAR(500) NULL,
    status          ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
    id          VARCHAR(10)  NOT NULL PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    building    VARCHAR(150) NOT NULL,
    floor       VARCHAR(10)  NOT NULL,
    capacity    VARCHAR(20)  NOT NULL,
    price_gov   DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_local DECIMAL(10,2) NOT NULL DEFAULT 0,
    equipment   JSON NOT NULL,
    status      VARCHAR(20)  NOT NULL DEFAULT 'Available',
    rules       TEXT NULL,
    warnings    TEXT NULL,
    locked      TINYINT(1) NOT NULL DEFAULT 0,
    image       VARCHAR(500) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id              VARCHAR(20)  NOT NULL PRIMARY KEY,
    room_id         VARCHAR(10)  NOT NULL,
    user_id         VARCHAR(10)  NULL,
    title           VARCHAR(255) NOT NULL,
    org_type        VARCHAR(50)  NOT NULL,
    department      VARCHAR(150) NOT NULL,
    organizer       VARCHAR(150) NOT NULL,
    phone           VARCHAR(30)  NULL,
    email           VARCHAR(150) NULL,
    date            DATE NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    participants    INT UNSIGNED NOT NULL DEFAULT 0,
    equipment       JSON NOT NULL,
    coffee_break    TINYINT(1) NOT NULL DEFAULT 0,
    remarks         TEXT NULL,
    status          ENUM('Waiting Approval','Approved','Rejected','Cancelled','Completed') NOT NULL DEFAULT 'Waiting Approval',
    duration_hours  DECIMAL(4,1) NOT NULL DEFAULT 0,
    rental_fee      DECIMAL(10,2) NOT NULL DEFAULT 0,
    reviewed_by     VARCHAR(10)  NULL,
    review_note     TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_bookings_room_date (room_id, date),
    INDEX idx_bookings_status (status),
    CONSTRAINT fk_bookings_room FOREIGN KEY (room_id) REFERENCES rooms (id),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repairs (
    id              VARCHAR(20)  NOT NULL PRIMARY KEY,
    room_id         VARCHAR(10)  NOT NULL,
    reported_by     VARCHAR(10)  NULL,
    technician_id   VARCHAR(10)  NULL,
    date            DATE NOT NULL,
    category        VARCHAR(50)  NOT NULL,
    description     TEXT NOT NULL,
    priority        ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    status          ENUM('Waiting','Accepted','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Waiting',
    before_image    VARCHAR(500) NULL,
    after_image     VARCHAR(500) NULL,
    repair_notes    TEXT NULL,
    material_used   TEXT NULL,
    labor_cost      DECIMAL(10,2) NOT NULL DEFAULT 0,
    material_cost   DECIMAL(10,2) NOT NULL DEFAULT 0,
    start_time      TIME NULL,
    finish_time     TIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_repairs_status (status),
    CONSTRAINT fk_repairs_room FOREIGN KEY (room_id) REFERENCES rooms (id),
    CONSTRAINT fk_repairs_reporter FOREIGN KEY (reported_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_repairs_technician FOREIGN KEY (technician_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- user_id = NULL คือแจ้งเตือนกลุ่ม: audience = กลุ่มบทบาทใน config/roles.php (NULL = ทุกคน)
CREATE TABLE IF NOT EXISTS notifications (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     VARCHAR(10)  NULL,
    audience    VARCHAR(30)  NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT NULL,
    icon        VARCHAR(50)  NOT NULL DEFAULT 'info',
    color       VARCHAR(20)  NOT NULL DEFAULT 'primary',
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_id, is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- สถานะอ่านแล้ว/ลบของผู้ใช้แต่ละคน (แจ้งเตือนกลุ่มหนึ่งรายการมีหลายผู้อ่าน)
CREATE TABLE IF NOT EXISTS notification_states (
    notification_id INT UNSIGNED NOT NULL,
    user_id         VARCHAR(10)  NOT NULL,
    read_at         DATETIME NULL,
    deleted_at      DATETIME NULL,
    PRIMARY KEY (notification_id, user_id),
    CONSTRAINT fk_nstate_notification FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE,
    CONSTRAINT fk_nstate_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     VARCHAR(10)  NULL,
    username    VARCHAR(150) NOT NULL,
    action      VARCHAR(100) NOT NULL,
    details     TEXT NULL,
    ip_address  VARCHAR(45)  NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ค่าตั้งค่าระบบแบบ key/value (เช่น system, working_hours)
CREATE TABLE IF NOT EXISTS settings (
    `key`       VARCHAR(50) NOT NULL PRIMARY KEY,
    value       JSON NOT NULL,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS holidays (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    date    DATE NOT NULL UNIQUE,
    name    VARCHAR(150) NOT NULL,
    type    VARCHAR(50)  NOT NULL DEFAULT 'วันหยุดราชการ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leave_types (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(20)  NOT NULL UNIQUE,
    name            VARCHAR(100) NOT NULL,
    quota_days      INT UNSIGNED NOT NULL DEFAULT 0,
    approval_level  ENUM('Supervisor','Department Head','Executive') NOT NULL DEFAULT 'Supervisor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     VARCHAR(10) NOT NULL,
    date        DATE NOT NULL,
    clock_in    TIME NULL,
    clock_out   TIME NULL,
    status      ENUM('normal','late','ot','leave','absent') NOT NULL DEFAULT 'normal',
    remarks     VARCHAR(255) NULL,
    UNIQUE KEY uq_attendance_user_date (user_id, date),
    CONSTRAINT fk_attendance_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leave_requests (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id         VARCHAR(10)  NOT NULL,
    leave_type_id   INT UNSIGNED NOT NULL,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    days            DECIMAL(4,1) NOT NULL,
    reason          TEXT NOT NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by     VARCHAR(10)  NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_leave_user (user_id, status),
    CONSTRAINT fk_leave_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_leave_type FOREIGN KEY (leave_type_id) REFERENCES leave_types (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
