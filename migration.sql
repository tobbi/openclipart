-- DATABASE MIGRATION FILE

DROP TABLE IF EXISTS openclipart_users;
DROP TABLE IF EXISTS openclipart_files;
DROP TABLE IF EXISTS openclipart_remixes;
DROP TABLE IF EXISTS openclipart_favorites;
DROP TABLE IF EXISTS openclipart_comments;
DROP TABLE IF EXISTS openclipart_clipart_issues;
DROP TABLE IF EXISTS openclipart_tags;
DROP TABLE IF EXISTS openclipart_clipart_tags;
DROP TABLE IF EXISTS openclipart_groups;
DROP TABLE IF EXISTS openclipart_user_groups;
DROP TABLE IF EXISTS openclipart_file_usage;

-- FILES
CREATE TABLE openclipart_files(id integer NOT NULL auto_increment, filename varchar(255), title varchar(255), seo_name varchar(255), owner integer NOT NULL, sha1 varchar(40), downloads integer, hidden boolean default 0, created datetime, modifed datetime, PRIMARY KEY(id), FOREIGN KEY(owner) REFERENCES openclipart_users(id));

INSERT INTO openclipart_files(id, filename, title, owner, sha1, downloads, hidden, nsfw, created) SELECT ocal_files.id, filename, upload_name, userid, sha1, file_num_download, not upload_published, upload_date FROM ocal_files INNER JOIN aiki_users ON aiki_users.username = ocal_files.user_name;

-- USERS
CREATE TABLE openclipart_users(id integer NOT NULL auto_increment, user_name varchar(255), password varchar(60), full_name varchar(255), country varchar(255), email varchar(255), avatar integer, homepage varchar(255), user_group integer, creation_date datetime, notify boolean, nsfw_filter boolean, rand_key varchar(40), PRIMARY KEY(id), FOREIGN KEY(user_group) REFERENCES openclipart_users_groups(id), FOREIGN KEY(avatar) REFERENCES openclipart_files(id));

-- copy non duplicate aiki_users
INSERT INTO openclipart_users(id, user_name, password, full_name, country, email, avatar, homepage, user_group, creation_date, notify, nsfw_filter) SELECT minids.userid, username, password, full_name, country, email, case RIGHT(avatar, 3) when 'svg' then (select openclipart_files.id from openclipart_files where filename = users.avatar AND users.userid = owner) else null end as avatar, homepage, usergroup, first_login, notify, nsfwfilter FROM aiki_users users INNER JOIN (SELECT MIN(userid) as userid FROM aiki_users GROUP by username) minids ON minids.userid = users.userid;


-- REMIXES

CREATE TABLE openclipart_remixes(clipart integer NOT NULL, original integer NOT NULL, PRIMARY KEY(clipart, original), FOREIGN KEY(clipart) REFERENCES openclipart_files(id), FOREIGN KEY(original) REFERENCES openclipart_files(id));

INSERT INTO openclipart_remixes SELECT distinct tree_child, tree_parent FROM cc_tbl_tree;

-- FAVORITES

CREATE TABLE openclipart_favorites(clipart integer NOT NULL, user integer NOT NULL, date datetime, PRIMARY KEY(clipart, user), FOREIGN KEY(clipart) REFERENCES openclipart_files(id), FOREIGN KEY(user) REFERENCES openclipart_users(id));

INSERT INTO openclipart_favorites SELECT DISTINCT openclipart_files.id, openclipart_users.id FROM ocal_favs LEFT JOIN openclipart_files ON openclipart_files.id = clipart_id LEFT JOIN openclipart_users ON ocal_favs.username = openclipart_users.user_name

-- COMMENTS

CREATE TABLE openclipart_comments(id integer NOT NULL auto_increment, user integer NOT NULL, comment text, date datetime, PRIMARY KEY(id), FOREIGN KEY(user) REFERENCES openclipart_users(id));

INSERT INTO openclipart_comments(user, comment, date) SELECT topic_user, topic_upload, topic_date FROM cc_tbl_topics;

-- ISSUES [NEW]

CREATE TABLE openclipart_clipart_issues(id integer NOT NULL auto_increment, date datetime, clipart ineger NOT NULL, user integer NOT NULL, title VARCHAR(255), comment TEXT, PRIMARY KEY(id), FOREIGN KEY(clipart) REFERENCES openclipart_files(id), FOREIGN KEY(user) REFERENCES openclipart_user(id));


-- TAGS

CREATE TABLE openclipart_tags(id integer NOT NULL auto_increment, name varchar(255), PRIMARY KEY(id, name));

CREATE TABLE openclipart_clipart_tags(clipart integer NOT NULL, tag integer NOT NULL, PRIMARY KEY(clipart, tag), FOREIGN KEY(clipart) REFERENCES openclipart_files(id), FOREIGN KEY(tag) REFERENCES openclipart_tags(id));

-- NSFW TAG

INSERT IGNORE INTO openclipart_clipart_tags SELECT id, (SELECT id FROM openclipart_tags WHERE name = 'nsfw') FROM ocal_files where nsfw = 1)

-- GROUPS

CREATE TABLE openclipart_groups(id integer NOT NULL auto_increment, name varchar(255), PRIMARY KEY(id));

INSERT INTO openclipart_groups VALUES(1, 'Admin'), (2, 'Librarian');

CREATE TABLE openclipart_user_groups(user_group integer NOT NULL, user integer NOT NULL, PRIMARY KEY(user_group, user), FOREIGN KEY(user_group) REFERENCES openclipart_groups(id), FOREIGN KEY(user) REFERENCES openclipart_users(id));


-- CLIPART in USE [NEW]

CREATE TABLE openclipart_file_usage(id integer NOT NULL auto_increment, filename VARCHAR(255), clipart integer NOT NULL, primary key(id), FOREIGN KEY(clipart) REFERENCES openclipart_files(id));
