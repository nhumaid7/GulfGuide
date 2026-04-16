
CREATE PROCEDURE GetPopularPosts(
    IN p_start_date DATE,
    IN p_end_date   DATE
)
BEGIN
    SELECT 
        p.post_id,
        p.title,
        p.created_at,
        p.view_count,
        u.username AS author,
        c.name AS country,
        COUNT(CASE WHEN r.type = 'like' THEN 1 END)    AS likes,
        COUNT(CASE WHEN r.type = 'dislike' THEN 1 END) AS dislikes
    FROM dbProj_post p
    JOIN dbProj_user u       ON p.user_id    = u.user_id
    JOIN dbProj_country c    ON p.country_id = c.country_id
    LEFT JOIN dbProj_reaction r ON p.post_id = r.post_id
    WHERE p.status = 'published'
      AND DATE(p.created_at) BETWEEN p_start_date AND p_end_date
    GROUP BY 
        p.post_id, p.title, p.created_at,
        p.view_count, u.username, c.name
    ORDER BY likes DESC, p.view_count DESC;
END$$


CREATE PROCEDURE GetPostsByUser(
    IN p_user_id INT
)
BEGIN
    SELECT 
        p.post_id,
        p.title,
        p.status,
        p.created_at,
        p.view_count,
        c.name AS country,
        COUNT(CASE WHEN r.type = 'like' THEN 1 END)    AS likes,
        COUNT(CASE WHEN r.type = 'dislike' THEN 1 END) AS dislikes,
        COUNT(cm.comment_id)                            AS comments
    FROM dbProj_post p
    JOIN dbProj_country c       ON p.country_id = c.country_id
    LEFT JOIN dbProj_reaction r ON p.post_id    = r.post_id
    LEFT JOIN dbProj_comment cm ON p.post_id    = cm.post_id
    WHERE p.user_id = p_user_id
    GROUP BY 
        p.post_id, p.title, p.status,
        p.created_at, p.view_count, c.name
    ORDER BY p.created_at DESC;
END$$