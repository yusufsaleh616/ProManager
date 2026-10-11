-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 04:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: grad_db
--
CREATE DATABASE IF NOT EXISTS grad_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE grad_db;

-- --------------------------------------------------------

--
-- Table structure for table admins
--

CREATE TABLE admins (
  admin_id int(11) NOT NULL,
  name varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  password varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table admins
--

INSERT INTO admins (admin_id, name, email, password) VALUES
(1, 'مدير النظام', 'admin@aabu.edu.jo', '$2y$10$QJjH8UTzHZuvE1fGzCA0weaL/xkSoGlhWJ2IhaFpFqUn4pr7Uz6aa');

-- --------------------------------------------------------

--
-- Table structure for table announcements
--

CREATE TABLE announcements (
  ann_id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(200) NOT NULL,
  content text NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (ann_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table colleges
--

CREATE TABLE colleges (
  college_code varchar(5) NOT NULL,
  college_name varchar(100) NOT NULL,
  PRIMARY KEY (college_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table colleges
--

INSERT INTO colleges (college_code, college_name) VALUES
('09', 'تكنولوجيا المعلومات');

-- --------------------------------------------------------

--
-- Table structure for table supervisors
--

CREATE TABLE supervisors (
  supervisor_id varchar(10) NOT NULL,
  name varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  password varchar(255) NOT NULL,
  dept_code varchar(5) NOT NULL,
  last_seen_announcements timestamp NOT NULL DEFAULT '2000-01-01 08:00:00',
  needs_password_change tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (supervisor_id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table supervisors
--

INSERT INTO supervisors (supervisor_id, name, email, password, dept_code, last_seen_announcements, needs_password_change) VALUES
('1732', ' يوسف', 'dryusuf@aabu.edu.jo', '$2y$10$WdKVfxQUiSk6apcuR0slseOHAb0kGDUdzFxzHYe1XMTrCJotYB3Km', '08', '2000-01-01 08:00:00', 0),
('1807', ' علي', 'drali@aabu.edu.jo', '$2y$10$yejayODpIO/dp7jAoE3j4u0czbMLInx9ysuMsqG5Sn5tGvS9tUbxu', '01', '2000-01-01 08:00:00', 0),
('2110', 'سامي', 'drsami@aabu.edu.jo', '$2y$10$/QSLPe.OW0LLUvlyPwzyju7GtAc73xG6mvbsj6RkCkO5gPacKUg7S', '01', '2000-01-01 08:00:00', 0),
('2150', ' سيف', 'drsaif@aabu.edu.jo', '$2y$10$ZAHSCXEkI9ILoPr9bLhnWuv1Be8NDKjBDqYTNXI55cR1x95ELR2hK', '08', '2000-01-01 08:00:00', 0),
('2201', ' خالد', 'drkhaled@aabu.edu.jo', '$2y$10$yOPIlyKCmNkha0ABsW2J4envW1pH5oDEuKiWrRxwppcphozIBiKy6', '02', '2000-01-01 08:00:00', 0),
('2202', 'رامي', 'drrami@aabu.edu.jo', '$2y$10$ALpgrCA/a.Q2VgCTbdhqsecfmc3qiF6cheYUYafnEypNF5DgyLHqa', '02', '2000-01-01 08:00:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table project_1
--

CREATE TABLE project_1 (
  project_id varchar(10) NOT NULL,
  student_id varchar(20) DEFAULT NULL,
  project_title varchar(255) NOT NULL,
  project_desc varchar(100) NOT NULL,
  project_file varchar(255) NOT NULL,
  supervisor_id varchar(10) DEFAULT NULL,
  grade int(11) DEFAULT NULL,
  notes text DEFAULT NULL,
  is_note_new tinyint(1) DEFAULT 0,
  PRIMARY KEY (project_id),
  UNIQUE KEY student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table project_1
--

INSERT INTO project_1 (project_id, student_id, project_title, project_desc, project_file, supervisor_id, grade, notes, is_note_new) VALUES
('0101', '2200901012', 'تطبيق لبيع وشراء المنتجات', 'تطبيق يتم فيه عرض المنتجات حسب الاقسام ويتم فيه بيع المنتجات', '1778926364p12200901012.pdf', '1807', 85, 'المشرف علي:فكرة مشروع جيده راجعني يوم الاحد في مكتبي على الساعة 10', 0),
('0201', '2200902090', 'نظام المفقودات والموجودات', 'مشروع يخدم الطلاب اللذين يفقدون ممتلكاتهم بلجامعة', '1778927936p12200902090.pdf', '2201', 80, 'مشروع جيد ينقصه بعض الملاحظات يرجى مراجعتي في مكتبي على الساعة 10 صباحا', 0),
('0202', '2200902091', 'موقع للوجهات السياحية في الأردن', 'نظام يهدف للتعرف على المناطق السياحيه في الاردن ويتم من خلاله الدفع والحجز عن بعد', '1778928425p12200902091.pdf', NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table project_2
--

CREATE TABLE project_2 (
  project_id varchar(10) NOT NULL,
  student_id varchar(20) DEFAULT NULL,
  project_title varchar(255) NOT NULL,
  source_code_file varchar(255) NOT NULL,
  supervisor_id varchar(10) DEFAULT NULL,
  grade int(11) DEFAULT NULL,
  notes text DEFAULT NULL,
  is_note_new tinyint(1) DEFAULT 0,
  PRIMARY KEY (project_id),
  UNIQUE KEY student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table project_2
--

INSERT INTO project_2 (project_id, student_id, project_title, source_code_file, supervisor_id, grade, notes, is_note_new) VALUES
('0101', '2200901012', 'تطبيق لبيع وشراء المنتجات', '1778929075p22200901012.zip', '1807', 90, 'راجعني في المكتب على الساعه 11 صباحا', 0),
('0201', '2200902090', 'نظام المفقودات والموجودات', '1778870877p22200902090.zip', '2201', 70, 'راجعني في المكتب على الساعه 10', 0);

-- --------------------------------------------------------

--
-- Table structure for table students
--

CREATE TABLE students (
  student_id varchar(15) NOT NULL,
  name varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  password varchar(255) NOT NULL,
  dept_code varchar(5) NOT NULL,
  last_seen_announcements timestamp NOT NULL DEFAULT '2000-01-01 08:00:00',
  needs_password_change tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (student_id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table students
--

INSERT INTO students (student_id, name, email, password, dept_code, last_seen_announcements, needs_password_change) VALUES
('2200901012', 'محمد احمد', '2200901012@st.aabu.edu.jo', '$2y$10$EyrabZM89MlZzTEh5cqRIOFniflolw44mG9w.AJc0RchqKA4q9i.W', '01', '2000-01-01 08:00:00', 0),
('2200901034', 'مهند سامي', '2200901034@st.aabu.edu.jo', '$2y$10$Dg2sZMvFJRiRxcb6B7ujCu3W6jMOnbie6PN6TgHXeIC3bcdWxSh3m', '01', '2000-01-01 08:00:00', 0),
('2200902090', 'ماجد جميل', '2200902090@st.aabu.edu.jo', '$2y$10$e71GIkLrQVq9EGh.q3uBzOs88Qx2Jo0ghwKFer7YoG.JclMGyDewK', '02', '2000-01-01 08:00:00', 0),
('2200902091', 'يوسف ماهر', '2200902091@st.aabu.edu.jo', '$2y$10$.g5e3t39YJLuR7AkH63VXuueMf8rSL1tB52az38U8n0B6l1U.vau6', '02', '2000-01-01 08:00:00', 0),
('2200908045', 'لين خالد', '2200908045@st.aabu.edu.jo', '$2y$10$zIWvez0bfPIKbzMx/gv7AOQXbNcosNzW4remsKL27bjkC1hMvneem', '08', '2000-01-01 08:00:00', 0),
('2200908099', 'دارين العلي', '2200908099@st.aabu.edu.jo', '$2y$10$X/eCI8wOZTjYJqsUO96Yp.a.HBvF4xk8jUxPheiZvyhhyYw5v0Rc.', '08', '2000-01-01 08:00:00', 0);

--
-- AUTO_INCREMENT for table admins
--
ALTER TABLE admins
  MODIFY admin_id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;