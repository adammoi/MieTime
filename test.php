<?php
echo '<h1>Mod Rewrite Test</h1>';
if (in_array('mod_rewrite', apache_get_modules())) {
    echo '<p style="color:green;">✅ mod_rewrite is ENABLED</p>';
} else {
    echo '<p style="color:red;">❌ mod_rewrite is DISABLED</p>';
}
