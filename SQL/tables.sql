/** SCHEMA CREATION */

DROP TABLE IF EXISTS `node_tree`;

CREATE TABLE `node_tree` (
  `idNode` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `level` int(11) DEFAULT NULL,
  `iLeft` int(11) DEFAULT NULL,
  `iRight` int(11) DEFAULT NULL,
  PRIMARY KEY (`idNode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




DROP TABLE IF EXISTS `node_tree_names`;

CREATE TABLE `node_tree_names` (
  `idNode` int(11) unsigned NOT NULL DEFAULT '0',
  `language` varchar(50) NOT NULL DEFAULT '',
  `nodeName` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idNode`,`language`),
  CONSTRAINT `fk_id_node` FOREIGN KEY (`idNode`) REFERENCES `node_tree` (`idNode`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


