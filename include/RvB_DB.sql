-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 17, 2014 at 10:18 AM
-- Server version: 5.5.38-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;




--
-- Database: `RvB`
--
-- CREATE DATABASE IF NOT EXISTS `RvB` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
-- USE `RvB`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`) VALUES
(1);

-- --------------------------------------------------------

--
-- Table structure for table `attempts`
--

DROP TABLE IF EXISTS `attempts`;
CREATE TABLE IF NOT EXISTS `attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Attempt ID',
  `teamid` int(11) NOT NULL COMMENT 'ID of the team submitting the flag',
  `flagSubmitted` varchar(100) NOT NULL COMMENT 'The value submitted by the team',
  `timeSubmitted` datetime NOT NULL COMMENT 'The time the flag was submitted',
  `correct` smallint(6) NOT NULL COMMENT 'Was the attempt correct',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `challenge_instance`
--

DROP TABLE IF EXISTS `challenge_instance`;
CREATE TABLE IF NOT EXISTS `challenge_instance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parentId` int(11) NOT NULL,
  `teamId` int(11) NOT NULL,
  `flagId` int(11) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `challenge_templates`
--

DROP TABLE IF EXISTS `challenge_templates`;
CREATE TABLE IF NOT EXISTS `challenge_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID of the table, should not be manually specified',
  `name` text NOT NULL COMMENT 'The name of the challenge',
  `image` blob NOT NULL COMMENT 'The challenges image. Not the best practise but these images are ~1kb so not an issue',
  `author` text NOT NULL COMMENT 'The Author of the challenge. Got to give credit where credit is due',
  `enabled` smallint(6) NOT NULL COMMENT 'Used to allow challenges to be disabled (ie not all challenges are available at all times)',
  `value` int(11) NOT NULL COMMENT 'How many points flags for this challenge are worth',
  `consoleOutput` text NOT NULL COMMENT 'Stores the console output generated while setting up this challenge',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` datetime NOT NULL COMMENT 'Starting time of the game',
  `duration` float NOT NULL COMMENT 'The number of hours the game will run for',
  `motd` text NOT NULL COMMENT 'Message of the day to display on the home page. By default this will be a stock standard warning warning players that this is a closed environment and they shouldn''t try what they learn in this game against targets that they don''t have permission to attackc',
  `rules` text NOT NULL,
  `leftHeader` text NOT NULL COMMENT 'The three header values are used to build a custom header',
  `centerHeader` text NOT NULL COMMENT 'They will each be in the following format, "value":"style"',
  `rightHeader` text NOT NULL COMMENT 'This will then be retrieved and a new header dynamically built',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`id`, `startTime`, `duration`, `motd`, `rules`, `leftHeader`, `centerHeader`, `rightHeader`) VALUES
(1, '2014-10-16 18:00:00', 6, 'Welcome to Team 14AA''s Red vs Blue game.\n\nPlease note, you have been given permission to attack the vulnerable services within the game environment. Under no circumstance should you use what you learn whilst playing against a system that you do not have permission to attack.', 'Don''t attack the game infrastructure (scoring server), everything else is fair game\nDon''t generate large amounts of traffic (try and keep the automated scans to a minimum)\nDon''t brute force the flag submission page', 'Red<><>text-align: center;\nmargin: 200px auto;\nfont-family: "PressStart";\nfont-size: 80px;\ntext-transform: uppercase;\ncolor: #fff;text-shadow: 0 0 10px #000, 0 0 20px #000, 0 0 30px #000, 0 0 40px #ff0000, 0 0 70px #ff0000, 0 0 80px #ff0000, 0 0 100px #ff0000, 0 0 150px #ff0000;', 'vs<><>padding-left: 25px;\npadding-right: 25px;\ntext-align: center;\nmargin: 80px auto;\nfont-family: "PressStart";\nfont-size: 50px;\n text-transform: uppercase;\ncolor: #fff;text-shadow: 0 0 10px #fff, 0 0 20px #fff, 0 0 30px #fff, 0 0 40px #000, 0 0 70px #000, 0 0 80px #000, 0 0 100px #000, 0 0 150px #000;', 'Blue<><>text-align: center;\nmargin: 200px auto;\nfont-family: "PressStart";\nfont-size: 80px;\ntext-transform: uppercase;\ncolor: #fff;text-shadow: 0 0 10px #fff, 0 0 20px #fff, 0 0 30px #fff, 0 0 40px #0000ff, 0 0 70px #0000ff, 0 0 80px #0000ff, 0 0 100px #0000ff, 0 0 150px #0000ff;');

-- --------------------------------------------------------

--
-- Table structure for table `flag`
--

DROP TABLE IF EXISTS `flag`;
CREATE TABLE IF NOT EXISTS `flag` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID of the challenge that this flag is for',
  `flag` varchar(100) NOT NULL COMMENT 'The actual flag',
  `enabled` smallint(6) NOT NULL COMMENT 'Allows flags to be disabled (in the event they are leaked or another flag is required)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `rtmsMessageQueue`
--

DROP TABLE IF EXISTS `rtmsMessageQueue`;
CREATE TABLE IF NOT EXISTS `rtmsMessageQueue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` text NOT NULL COMMENT 'The type of message. Current options: message, flagCaptured, timeWarning',
  `message` text NOT NULL COMMENT 'The message to send',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
CREATE TABLE IF NOT EXISTS `teams` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `teamname` varchar(255) NOT NULL,
  `password` varchar(64) NOT NULL,
  `enabled` smallint(6) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '3' COMMENT '0=Admin, 1=Red, 2=Blue, 3=Purple',
  PRIMARY KEY (`id`),
  UNIQUE KEY `teamname` (`teamname`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `teamname`, `password`, `enabled`, `type`) VALUES
(1, 'rvbadmin', '5f4dcc3b5aa765d61d8327deb882cf99', 1, 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
