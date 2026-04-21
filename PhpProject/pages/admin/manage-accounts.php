<?php
// Manage user accounts
?>
<div class="container-fluid py-5">
    <h1>Manage Accounts</h1>
    
    <div class="table-responsive mt-4">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all users
                // $users = User::all();
                ?>
                <tr>
                    <td>1</td>
                    <td>User Name</td>
                    <td>user@example.com</td>
                    <td><span class="badge bg-primary">User</span></td>
                    <td>2024-01-01</td>
                    <td>
                        <form method="POST" action="/accounts/delete" style="display: inline;">
                            <input type="hidden" name="user_id" value="1">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete user?')">Delete</button>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
