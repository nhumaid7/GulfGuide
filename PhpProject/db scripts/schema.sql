CREATE TABLE dbProj_user (
    user_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(30) NOT NULL,
    email VARCHAR(50) NOT NULL,
    hashed_password VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id)
);

CREATE TABLE dbProj_creator_request (
    request_id INT NOT NULL AUTO_INCREMENT,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    reason VARCHAR(300) NOT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (request_id)
);

CREATE TABLE dbProj_attraction_type (
    type_id INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (type_id)
);

CREATE TABLE dbProj_country (
    country_id INT NOT NULL AUTO_INCREMENT,
    flag_image VARCHAR(255) NOT NULL,
    official_tourism_website VARCHAR(255) NOT NULL,
    display_image VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    description VARCHAR(500) NOT NULL,
    PRIMARY KEY (country_id)
);

CREATE TABLE dbProj_attraction (
    attraction_id INT NOT NULL AUTO_INCREMENT,
    country_id INT NOT NULL,
    type_id INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    description VARCHAR(500) NOT NULL,
    cover_image VARCHAR(255) NOT NULL,
    view_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (attraction_id)
);

CREATE TABLE dbProj_attraction_media (
    media_id INT NOT NULL AUTO_INCREMENT,
    attraction_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    media_type VARCHAR(20) NOT NULL,
    PRIMARY KEY (media_id)
);

CREATE TABLE dbProj_post (
    post_id INT NOT NULL AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    content VARCHAR(1000) NOT NULL,
    thumbnail VARCHAR(255) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    view_count INT NOT NULL DEFAULT 0,
    user_id INT NOT NULL,
    country_id INT NOT NULL,
    attraction_id INT NULL,
    PRIMARY KEY (post_id)
);

CREATE TABLE dbProj_post_media (
    media_id INT NOT NULL AUTO_INCREMENT,
    post_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    media_type VARCHAR(20) NOT NULL,
    PRIMARY KEY (media_id)
);

CREATE TABLE dbProj_reaction (
    reaction_id INT NOT NULL AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    `type` VARCHAR(10) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reaction_id),
    UNIQUE KEY unique_reaction (post_id, user_id)
);

CREATE TABLE dbProj_comment (
    comment_id INT NOT NULL AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content VARCHAR(300) NOT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (comment_id)
);


ALTER TABLE dbProj_creator_request ADD CONSTRAINT fk_creator_request_user
FOREIGN KEY (user_id) REFERENCES dbProj_user (user_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_attraction ADD CONSTRAINT fk_attraction_type
FOREIGN KEY (type_id) REFERENCES dbProj_attraction_type (type_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_attraction ADD CONSTRAINT fk_attraction_country
FOREIGN KEY (country_id) REFERENCES dbProj_country (country_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_attraction_media ADD CONSTRAINT fk_attraction_media_attraction
FOREIGN KEY (attraction_id) REFERENCES dbProj_attraction (attraction_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_post ADD CONSTRAINT fk_post_user
FOREIGN KEY (user_id) REFERENCES dbProj_user (user_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_post ADD CONSTRAINT fk_post_country
FOREIGN KEY (country_id) REFERENCES dbProj_country (country_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_post ADD CONSTRAINT fk_post_attraction
FOREIGN KEY (attraction_id) REFERENCES dbProj_attraction (attraction_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_post_media ADD CONSTRAINT fk_post_media_post
FOREIGN KEY (post_id) REFERENCES dbProj_post (post_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_reaction ADD CONSTRAINT fk_reaction_post
FOREIGN KEY (post_id) REFERENCES dbProj_post (post_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_reaction ADD CONSTRAINT fk_reaction_user
FOREIGN KEY (user_id) REFERENCES dbProj_user (user_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_comment ADD CONSTRAINT fk_comment_post
FOREIGN KEY (post_id) REFERENCES dbProj_post (post_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE dbProj_comment ADD CONSTRAINT fk_comment_user
FOREIGN KEY (user_id) REFERENCES dbProj_user (user_id)
ON DELETE NO ACTION ON UPDATE NO ACTION;