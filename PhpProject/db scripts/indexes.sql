ALTER TABLE dbProj_post ADD FULLTEXT INDEX ft_post_title (title);
ALTER TABLE dbProj_post ADD FULLTEXT INDEX ft_post_content (content);