DROP TABLE IF EXISTS lists;
CREATE TABLE lists (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    high_school CHAR(10),
    odds CHAR(10),
    ratio TINYINT UNSIGNED DEFAULT 0,
    score TINYINT UNSIGNED DEFAULT 0,
    win BOOL DEFAULT true,
    PRIMARY KEY (id)
);

DROP TABLE IF EXISTS selects;
CREATE TABLE selects (
  id INT NOT NULL AUTO_INCREMENT,
  hdn CHAR(100),
  password CHAR(100),
  ip CHAR(100),
  inputs1 CHAR(10),
  inputs2 CHAR(10),
  inputs3 CHAR(10),
  inputs4 CHAR(10),
  inputs5 CHAR(10),
  inputs6 CHAR(10),
  inputs7 CHAR(10),
  inputs8 CHAR(10),
  created TIMESTAMP,
  PRIMARY KEY (id)
);