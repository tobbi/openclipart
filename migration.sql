-- DATABASE MIGRATION FILE

CREATE TABLE openclipart_users(id integer NOT NULL auto_increment, user_name varchar(255), password varchar(60), full_name varchar(255), country varchar(255), email varchar(255), avatar integer, homepage varchar(255), user_group integer, creation_date datetime, notify boolean, nsfw_filter boolean, rand_key varchar(40), PRIMARY KEY(id), FOREIGN KEY(user_group) REFERENCES openclipart_users_groups(id), FOREIGN KEY(avatar) REFERENCES openclipart_files(id));

-- copy non duplicate aiki_users
INSERT INTO openclipart_users(id, user_name, password, full_name, country, email, avatar, homepage, user_group, creation_date, notify, nsfw_filter) SELECT minids.userid, username, password, full_name, country, email, case RIGHT(avatar, 3) when 'svg' then (select openclipart_files.id from openclipart_files where filename = users.avatar AND users.userid = owner) else null end as avatar, homepage, usergroup, first_login, notify, nsfwfilter FROM aiki_users users INNER JOIN (SELECT MIN(userid) as userid FROM aiki_users GROUP by username) minids ON minids.userid = users.userid;

-- FILES
CREATE TABLE openclipart_files(id integer NOT NULL auto_increment, filename varchar(255), title varchar(255), seo_name varchar(255), owner integer NOT NULL, sha1 varchar(40), downloads integer, hidden boolean default 0, nsfw boolean default 0, PRIMARY KEY(id), FOREIGN KEY(owner) REFERENCES openclipart_users(id));

INSERT INTO openclipart_files(id, filename, title, owner, sha1, downloads, hidden, nsfw) SELECT ocal_files.id, filename, upload_name, openclipart_users.id, sha1, file_num_download, not upload_published, nsfw FROM ocal_files INNER JOIN openclipart_users ON openclipart_users.user_name = ocal_files.user_name;

--- UP TO THIS ALL WORK DONE

CREATE TABLE openclipart_remixes(clipart integer NOT NULL, original integer NOT NULL, PRIMARY KEY(clipart, original), FOREIGN KEY(clipart) REFERENCES openclipart_files(id), FOREIGN KEY(original) REFERENCES openclipart_files(id));

INSERT INTO openclipart_remixes SELECT distinct tree_child, tree_parent FROM cc_tbl_tree;
----


UPDATE openclipart_users SET openclipart_users.avatar = openclipart_files.id FROM openclipart_files INNER JOIN (SELECT * FROM aiki_users WHERE avatar like '%svg') users ON avatar = filename;

UPDATE openclipart_users SET avatar = openclipart_files.id FROM openclipart_files WHERE filename = (SELECT avatar from aiki_users WHERE avatar like '%svg');

select count(openclipart_files.id) from openclipart_files, aiki_users  WHERE avatar = filename AND avatar <> '';

select count(openclipart_files.id) from (SELECT avatar FROM aiki_users WHERE avatar <> '') avatars, openclipart_files WHERE avatars.avatar = filename;
------------



----

SELECT openclipart_files.id FROM aiki_users INNER JOIN openclipart_files ON owner = userid WHERE aiki_users.avatar = openclipart_files.filename;





-- SELECT upload_user, userid FROM ocal_files, aiki_users WHERE user_name = username AND upload_user <> userid;

CREATE TABLE openclipart_favs(clipart integer NOT NULL, user integer NOT NULL, date datetime, PRIMARY KEY(clipart, user), FOREIGN KEY(clipart) REFERENCES openclipart_files(id), FOREIGN KEY(user) REFERENCES openclipart_users (id));






CREATE TABLE openclipart_tags(id integer NOT NULL auto_increment, name varchar(255), PRIMARY KEY(id));

CREATE TABLE openclipart_file_tags(id

CREATE TABLE openclipart_users_groups(id integer NOT NULL auto_increment, group_name varchar(255), PRIMARY KEY(id));

INSERT INTO openclipart_users_groups VALUES(1, 'Admin'), (2, 'Librarian'), (3, 'Normal');



-- copy avatars


UPDATE
    openclipart_users
SET
    openclipart_users.avatar = ocal_files.id
FROM
    Table
INNER JOIN
    other_table
ON
    ocal_files.id = .id


INSERT INTO new_table 
    SELECT email, user_name, password 
    FROM old_table 
    INNER JOIN 
        ( SELECT MIN(id) FROM old_table GROUP by user_name ) minids
    ON minids.id = old_table.id



(global-set-key (kbd "RET") 'newline)
