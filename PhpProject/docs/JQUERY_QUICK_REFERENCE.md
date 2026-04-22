# jQuery Quick Reference for GulfGuide Project

## Common jQuery Patterns & Examples

### 1. Show/Hide Elements
```javascript
// Hide element
$('#myElement').hide();

// Show element
$('#myElement').show();

// Toggle visibility
$('#myElement').toggle();

// Fade effects
$('#myElement').fadeIn();
$('#myElement').fadeOut();
$('#myElement').fadeToggle();

// Slide effects
$('#myElement').slideDown();
$('#myElement').slideUp();
$('#myElement').slideToggle();
```

### 2. Add/Remove Classes
```javascript
// Add class
$('#myElement').addClass('active');

// Remove class
$('#myElement').removeClass('active');

// Toggle class
$('#myElement').toggleClass('active');

// Check if has class
if ($('#myElement').hasClass('active')) {
    // Do something
}
```

### 3. Get/Set Values
```javascript
// Get value
const value = $('#myInput').val();

// Set value
$('#myInput').val('New value');

// Get text content
const text = $('#myElement').text();

// Set text content
$('#myElement').text('New text');

// Get/set HTML
$('#myElement').html('<p>New HTML</p>');

// Get/set attribute
const src = $('img').attr('src');
$('img').attr('src', 'new-image.jpg');

// Get/set data attribute
const data = $('#myElement').data('id');
$('#myElement').data('user-id', 123);
```

### 4. Event Handlers
```javascript
// Click event
$('#button').on('click', function() {
    console.log('Button clicked');
});

// Double click
$('#element').on('dblclick', function() {
    console.log('Double clicked');
});

// Mouse events
$('#element').on('mouseover', function() {});
$('#element').on('mouseout', function() {});
$('#element').on('mouseenter', function() {});
$('#element').on('mouseleave', function() {});

// Keyboard events
$('#input').on('keyup', function(e) {
    console.log('Key code:', e.which);
});
$('#input').on('keydown', function() {});
$('#input').on('keypress', function() {});

// Focus/Blur
$('#input').on('focus', function() {});
$('#input').on('blur', function() {});

// Form submit
$('form').on('submit', function(e) {
    e.preventDefault();
    // Don't submit form
});

// Document ready
$(document).ready(function() {
    // Page loaded, DOM is ready
});
```

### 5. DOM Manipulation
```javascript
// Create new element
const newDiv = $('<div class="new-class">Content</div>');

// Append (add inside, at end)
$('#container').append(newDiv);

// Prepend (add inside, at start)
$('#container').prepend(newDiv);

// Insert after
$('#myElement').after(newDiv);

// Insert before
$('#myElement').before(newDiv);

// Replace element
$('#oldElement').replaceWith($('#newElement'));

// Remove element
$('#myElement').remove();

// Empty element (remove children)
$('#container').empty();

// Clone element
const clone = $('#myElement').clone();
```

### 6. CSS Manipulation
```javascript
// Get CSS property
const color = $('#myElement').css('color');

// Set CSS property
$('#myElement').css('color', 'red');

// Set multiple CSS properties
$('#myElement').css({
    'color': 'red',
    'background': 'yellow',
    'padding': '20px'
});

// Get/set width & height
const width = $('#myElement').width();
$('#myElement').width('200px');

// Get/set outer width/height (with border)
const outerWidth = $('#myElement').outerWidth();

// Offset from document
const offset = $('#myElement').offset();
console.log(offset.top, offset.left);

// Position relative to parent
const position = $('#myElement').position();
```

### 7. AJAX Requests (For PHP API calls)
```javascript
// Simple GET request
$.get('api/users.php', function(data) {
    console.log(data);
});

// Simple POST request
$.post('api/save.php', { name: 'John', email: 'john@example.com' }, function(data) {
    console.log(data);
});

// Full AJAX request with error handling
$.ajax({
    url: 'api/users.php',
    type: 'GET',
    dataType: 'json',
    timeout: 5000,
    success: function(data) {
        console.log('Success:', data);
        window.showAlert('Data loaded!', 'success');
    },
    error: function(xhr, status, error) {
        console.error('Error:', error);
        window.showAlert('Failed to load data', 'danger');
    },
    complete: function() {
        // Always executed
        console.log('AJAX complete');
    }
});

// POST with file upload
$.ajax({
    url: 'api/upload.php',
    type: 'POST',
    data: new FormData($('#myForm')[0]),
    processData: false,
    contentType: false,
    success: function(response) {
        console.log(response);
    }
});
```

### 8. Form Handling
```javascript
// Get all form data
const formData = $('form').serialize();
console.log(formData); // name=John&email=john@email.com

// Get form data as object
const formObj = {};
$('#myForm').serializeArray().forEach(function(item) {
    formObj[item.name] = item.value;
});

// Validate form
function validateForm() {
    let isValid = true;
    $('form').find('[required]').each(function() {
        if ($(this).val().trim() === '') {
            isValid = false;
            $(this).addClass('is-invalid');
        }
    });
    return isValid;
}

// Clear form
$('form')[0].reset();
// or
$('form').find('input, textarea, select').val('');

// Disable form elements
$('form').find('input, button, textarea').prop('disabled', true);

// Enable form elements
$('form').find('input, button, textarea').prop('disabled', false);
```

### 9. Array & Iteration
```javascript
// Iterate over jQuery selection
$('tr').each(function(index, element) {
    console.log(index, $(element).text());
});

// Map to array
const texts = $('li').map(function() {
    return $(this).text();
}).get();

// Filter elements
const active = $('li').filter('.active');

// First/Last element
$('li').first();
$('li').last();

// Get specific element
$('li').eq(2); // 3rd element

// Get all matching elements
$('li').length;
```

### 10. Animation & Effects
```javascript
// Basic animation
$('#myElement').animate({
    left: '250px',
    opacity: 0.5
}, 1000); // 1 second duration

// Chained animations
$('#myElement')
    .fadeIn(500)
    .slideDown(300)
    .delay(1000)
    .fadeOut(500);

// Stop animation
$('#myElement').stop();

// Stop and jump to end
$('#myElement').stop(true, true);

// Delay (pause between animations)
$('#myElement').delay(2000).fadeOut();
```

### 11. GulfGuide Project Specific Examples

#### Show Alert
```javascript
window.showAlert('Item saved successfully!', 'success');
window.showAlert('An error occurred!', 'danger');
window.showAlert('Please wait...', 'info');
```

#### Confirm Dialog
```javascript
window.showConfirm('Delete this item?', function(confirmed) {
    if (confirmed) {
        // Delete item
        console.log('Item deleted');
    } else {
        // Cancelled
        console.log('Cancelled');
    }
});
```

#### Load Sidebar
```javascript
$('.menu-toggle').on('click', function() {
    $('.sidebar').toggleClass('show');
});
```

#### Add Active Class to Menu Item
```javascript
const currentPage = 'moderatePosts.php';
$('a[href*="' + currentPage + '"]')
    .closest('.sidebar-nav-item')
    .find('> a')
    .addClass('active');
```

#### Populate Table from AJAX
```javascript
$.ajax({
    url: 'api/get-users.php',
    success: function(users) {
        let html = '';
        users.forEach(function(user) {
            html += `<tr>
                <td>${user.name}</td>
                <td>${user.email}</td>
                <td><span class="badge bg-success">Active</span></td>
            </tr>`;
        });
        $('#usersTable tbody').html(html);
    }
});
```

#### Form with AJAX Submit
```javascript
$('#myForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    
    $.ajax({
        url: 'api/save-form.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            window.showAlert('Saved successfully!', 'success');
            // Refresh page or update UI
        },
        error: function() {
            window.showAlert('Error saving data', 'danger');
        }
    });
});
```

### 12. Helpful Utilities

#### Debounce (delay function execution)
```javascript
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

// Usage
const onSearch = debounce(function(query) {
    console.log('Searching for:', query);
}, 300);

$('#search').on('keyup', function() {
    onSearch($(this).val());
});
```

#### Format Date
```javascript
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

console.log(formatDate('2024-01-15')); // Jan 15, 2024
```

#### Get URL Parameter
```javascript
function getUrlParameter(name) {
    const url = new URLSearchParams(window.location.search);
    return url.get(name);
}

const userId = getUrlParameter('id');
```

---

## Bootstrap Classes Reference

### Grid & Layout
```html
<!-- Container -->
<div class="container"><!-- Fixed width --></div>
<div class="container-fluid"><!-- Full width --></div>

<!-- Row & Columns -->
<div class="row">
    <div class="col">Auto width</div>
    <div class="col-6">50% width</div>
    <div class="col-md-6">50% on medium+ screens</div>
    <div class="col-lg-4">33% on large+ screens</div>
</div>

<!-- Flex utilities -->
<div class="d-flex justify-content-between align-items-center">
    <!-- Flex container with space-between -->
</div>
```

### Spacing
```html
<!-- m = margin, p = padding -->
<!-- t, b, l, r, x (left+right), y (top+bottom) -->
<div class="mt-5">Margin top</div>
<div class="mb-3">Margin bottom</div>
<div class="px-4">Padding left & right</div>
<div class="py-2">Padding top & bottom</div>
```

### Text
```html
<p class="text-center">Center text</p>
<p class="text-muted">Gray text</p>
<p class="text-primary">Blue text</p>
<p class="text-danger">Red text</p>
<p class="text-success">Green text</p>
<p class="fw-bold">Bold text</p>
<p class="text-capitalize">Capitalize Text</p>
<p class="text-uppercase">UPPERCASE TEXT</p>
<p class="text-lowercase">lowercase text</p>
```

### Common Components
```html
<!-- Badge -->
<span class="badge bg-primary">Primary</span>
<span class="badge bg-success">Success</span>

<!-- Alert -->
<div class="alert alert-info">Info message</div>
<div class="alert alert-danger">Danger message</div>

<!-- Button -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-outline-primary">Outline</button>

<!-- Card -->
<div class="card">
    <div class="card-header">Header</div>
    <div class="card-body">Content</div>
</div>

<!-- List Group -->
<ul class="list-group">
    <li class="list-group-item">Item 1</li>
    <li class="list-group-item active">Item 2</li>
</ul>
```

---

## Debugging Tips

### Check jQuery is loaded
```javascript
if (typeof jQuery !== 'undefined') {
    console.log('jQuery is loaded');
} else {
    console.log('jQuery is NOT loaded');
}
```

### Console logging
```javascript
console.log('Value:', variable);
console.warn('Warning:', error);
console.error('Error:', error);
console.table(arrayOfObjects); // Pretty print arrays
```

### Inspect element
```javascript
// In browser console
$('#myElement').inspect(); // Opens inspector

// Or use dev tools shortcut: F12
```

### Check for errors
```javascript
// In browser console
// Look for red error messages
// Click on them to see file and line number
```

---

## Performance Tips

1. **Cache jQuery selections**
   ```javascript
   // Bad
   $('#button').on('click', () => $('#result').text('clicked'));
   
   // Good
   const $button = $('#button');
   const $result = $('#result');
   $button.on('click', () => $result.text('clicked'));
   ```

2. **Use event delegation for dynamic elements**
   ```javascript
   // Good for elements added later
   $(document).on('click', '.delete-btn', function() {
       // Handle delete
   });
   ```

3. **Debounce search inputs**
   ```javascript
   $('#search').on('keyup', debounce(function() {
       performSearch($(this).val());
   }, 300));
   ```

4. **Use vanilla JS when possible**
   ```javascript
   // Better performance than jQuery
   document.querySelector('#element');
   document.querySelectorAll('.elements');
   ```

---

## Resources

- [jQuery Documentation](https://jquery.com/)
- [jQuery Cheat Sheet](https://oscarotero.com/jquery/)
- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.0/)
- [Bootstrap 5 Cheat Sheet](https://bootstrap-cheatsheet.themestr.app/)

