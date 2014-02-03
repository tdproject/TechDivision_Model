-- phpMyAdmin SQL Dump
-- version 3.2.2-rc1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 14, 2009 at 09:40 AM
-- Server version: 5.1.37
-- PHP Version: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Create the database `test_model `
--

CREATE DATABASE `test_model`;
USE `test_model`;

--
-- Database: `test_model`
--

-- --------------------------------------------------------

--
-- Table structure for table `test_model`
--

CREATE TABLE IF NOT EXISTS `test_model` (
  `test_model_id` int(11) NOT NULL AUTO_INCREMENT,
  `val` varchar(255) NOT NULL,
  PRIMARY KEY (`test_model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `test_model`
--

INSERT INTO `test_model` (`val`) VALUES
('Foo'),
('Bar');

CREATE TABLE IF NOT EXISTS `user` (
    `user_id` int(10) NOT NULL, 
    `firstname` varchar(255) NOT NULL, 
    `lastname` varchar(255) NOT NULL, 
    `email` varchar(255) NOT NULL default 1, 
    `username` varchar(50) NOT NULL, 
    `password` varchar(32) NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ENGINE=InnoDB;
        
ALTER TABLE `user` ADD CONSTRAINT user_pk PRIMARY KEY (`user_id`); 
ALTER TABLE `user` CHANGE user_id `user_id` int(10) AUTO_INCREMENT;

INSERT INTO `user` (`user_id`, `firstname`, `lastname`, `email`, `username`, `password`) VALUES
(1, 'Hans', 'Mustermann', 'hm@mustermann.de', 'mustermannh', 'musti01');

CREATE TABLE IF NOT EXISTS `project` (
    `project_id` int(10) NOT NULL, 
    `user_id_fk` int(10) NOT NULL, 
    `name` varchar(50) NOT NULL, 
    `description` text NOT NULL, 
    `generated_buildfile` text NOT NULL, 
    `generation_date` int(10) NOT NULL, 
    `mod_time` int(10) NOT NULL
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ENGINE=InnoDB;      

ALTER TABLE `project` ADD CONSTRAINT project_pk PRIMARY KEY (`project_id`); 
ALTER TABLE `project` CHANGE project_id `project_id` int(10) AUTO_INCREMENT;

CREATE INDEX project_idx_01 ON `project` (`user_id_fk`);

ALTER TABLE `project` ADD CONSTRAINT project_fk_01 FOREIGN KEY (`user_id_fk`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
        
