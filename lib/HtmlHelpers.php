<?php

// ============================================================================
// HTML Helpers
// ============================================================================

// Encode HTML special characters
function encode($value)
{
    return htmlentities($value);
}

// Generate <input type='text'>
function html_text($key, $attr = '', $id = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    $id = $id ?: $key;
    echo "<input type='text' id='$id' name='$key' value='$value' autocomplete='off' $attr>";
}


// Generate <input type='search'>
function html_search($key, $attr = '', $id = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    $id = $id ?: $key;
    echo "<input type='search' id='$id' name='$key' value='$value' autocomplete='off' $attr>";
}

// Generate <input type='password'>
function html_password($key, $attr = '', $id = '')
{
    $id = $id ?: $key;
    echo "<div class='password-input-wrapper'>";
    echo "<input type='password' id='$id' name='$key' autocomplete='off' $attr>";
    echo "<button type='button' class='password-toggle' aria-label='Toggle password visibility'>";
    echo "<img src='" . BASE_URL . "assets/images/icons/eye.svg' alt='Toggle'>";
    echo "</button>";
    echo "</div>";
}

// Generate <input type='email'>
function html_email($key, $attr = '', $id = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    $id = $id ?: $key;
    echo "<input type='email' id='$id' name='$key' value='$value' autocomplete='off' $attr>";
}

// Generate <input type='date'>
function html_date($key, $attr = '', $id = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    $id = $id ?: $key;
    echo "<input type='date' id='$id' name='$key' value='$value' autocomplete='off' $attr>";
}

// Generate <input type='file'>
function html_file($name, $id, $attr = '')
{
    echo "<input type='file' id='$id' name='$name' autocomplete='off' $attr>";
}

// Generate <input type='hidden'>
function html_hidden($key, $value = '', $attr = '')
{
    if (empty($value)) {
        $value = $GLOBALS[$key] ?? '';
    }
    echo "<input type='hidden' name='$key' value='" . encode($value) . "' $attr>";
}

// Generate <input type='checkbox'>
function html_checkbox($key, $value = '1', $attr = '')
{
    $checked = ($GLOBALS[$key] ?? '') == $value ? 'checked' : '';
    echo "<input type='checkbox' id='$key' name='$key' value='$value' $checked $attr>";
}

function html_phone($key, $attr = '')
{
    $countryCodes = [
        '+60' => 'MY +60',
        '+65' => 'SG +65',
        '+86' => 'CN +86',
        '+1' => 'US +1',
        '+44' => 'UK +44',
        '+61' => 'AU +61',
        '+81' => 'JP +81',
        '+82' => 'KR +82',
        '+91' => 'IN +91',
        '+62' => 'ID +62',
        '+63' => 'PH +63',
        '+66' => 'TH +66',
        '+84' => 'VN +84'
    ];

    $value = $GLOBALS[$key] ?? '';
    $prefix = '+60';
    $number = '';

    // Split existing value if it contains a prefix (format: +60-123456789)
    if (!empty($value) && preg_match('/^(\+\d+)-(.+)$/', $value, $matches)) {
        $prefix = $matches[1];
        $number = $matches[2];
    } elseif (!empty($value)) {
        $number = $value;
    }
    echo '<div class="phone-input-group">';
    echo "<select id='{$key}_prefix' name='{$key}_prefix' class='phone-prefix' autocomplete='off'>";
    foreach ($countryCodes as $code => $label) {
        $selected = $code === $prefix ? 'selected' : '';
        echo "<option value='$code' $selected>$label</option>";
    }
    echo '</select>';
    echo "<input type='text' id='$key' name='$key' value='" . encode($number) . "' placeholder='123456789' class='phone-number' autocomplete='off' $attr>";
    echo '</div>';
}

// Generate <input type='radio'> list
function html_radios($key, $items, $br = false)
{
    $value = encode($GLOBALS[$key] ?? '');
    echo '<div>';
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'checked' : '';
        echo "<label><input type='radio' id='{$key}_$id' name='$key' value='$id' $state>$text</label>";
        if ($br) {
            echo '<br>';
        }
    }
    echo '</div>';
}

// Generate <select>
function html_select($key, $items, $default = '- Select One -', $attr = '')
{
    $value = encode($GLOBALS[$key] ?? '');
    echo "<select id='$key' name='$key' autocomplete='off' $attr>";
    if ($default !== null) {
        echo "<option value=''>$default</option>";
    }
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'selected' : '';
        echo "<option value='$id' $state>$text</option>";
    }
    echo '</select>';
}

// Generate table headers <th>
function table_headers($fields, $sort, $dir, $href = '')
{
    foreach ($fields as $k => $v) {
        $d = 'asc'; // Default direction
        $c = '';    // Default class

        if ($k == $sort) {
            $d = $dir == 'asc' ? 'desc' : 'asc';
            $c = $dir;
        }

        echo "<th><a href='?sort=$k&dir=$d&$href' class='$c'>$v</a></th>";
    }
}
