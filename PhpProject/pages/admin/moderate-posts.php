<?php
// Moderate posts - admin can delete posts
?>
<div class="container-fluid py-5">
    <h1>Moderate Posts</h1>
    
    <div class="mt-4">
        <?php
        // Fetch flagged or all posts for moderation
        // $posts = Post::all();
        ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Post Title</td>
                        <td>Author Name</td>
                        <td>2024-01-01</td>
                        <td>
                            <a href="/posts/1" class="btn btn-sm btn-info">View</a>
                            <form method="POST" action="/posts/moderate" style="display: inline;">
                                <input type="hidden" name="post_id" value="1">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete post?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
