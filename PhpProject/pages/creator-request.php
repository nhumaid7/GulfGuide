<?php
// Creator request form
?>
<div class="container py-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <h1>Request Creator Status</h1>
            <p>Submit your request to become a creator on GulfGuide</p>
            
            <form method="POST" action="/creator-request" class="mt-4">
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Tell us why you want to be a creator..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </form>
        </div>
    </div>
</div>
