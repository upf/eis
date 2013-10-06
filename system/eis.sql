SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `devices` (
  `id` varchar(256) NOT NULL,
  `version` varchar(16) NOT NULL,
  `date` date NOT NULL,
  `author` varchar(256) NOT NULL,
  `class` varchar(256) NOT NULL,
  `type` varchar(256) NOT NULL,
  `ifport` int(10) unsigned NOT NULL,
  `description` text NOT NULL,
  `configurations` text NOT NULL,
  `published` varchar(5) NOT NULL,
  `installed` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `log` (
  `timestamp` int(10) unsigned NOT NULL,
  `device` varchar(256) NOT NULL,
  `level` varchar(32) NOT NULL,
  `message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `status` (
  `deviceID` varchar(100) NOT NULL,
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
