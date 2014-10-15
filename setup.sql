
-- Setup teams table
CREATE TABLE `teams` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `teamname` varchar(255) NOT NULL,
  `password` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teamname` (`teamname`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- Setup admins table. Any team id in this table will be treated as an admin (equivilant of the sudoers file in linux)
CREATE TABLE `admins` (
  `id` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--Setup challenges table. 
CREATE TABLE `challenges` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID of the table, should not be manually specified',
  `name` text NOT NULL COMMENT 'The name of the challenge',
  `image` blob NOT NULL COMMENT 'The challenges image. Not the best practise but these images are ~1kb so not an issue',
  `author` text NOT NULL COMMENT 'The Author of the challenge. Got to give credit where credit is due',
  `enabled` smallint(6) NOT NULL COMMENT 'Used to allow challenges to be disabled (ie not all challenges are available at all times)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--Setup Challenge config table
CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `startTime` date NOT NULL COMMENT 'Starting time of the game',
  `endTime` date NOT NULL COMMENT 'Ending time of the game. Generally set as startTime + game duration',
  `motd` text NOT NULL COMMENT 'Message of the day to display on the home page. By default this will be a stock standard warning warning players that this is a closed environment and they shouldn''t try what they learn in this game against targets that they don''t have permission to attack',
  `leftHeader` text NOT NULL COMMENT 'The three header values are used to build a custom header',
  `centerHeader` text NOT NULL COMMENT 'They will each be in the following format, "value":"style"',
  `rightHeader` text NOT NULL COMMENT 'This will then be retrieved and a new header dynamically built',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;
