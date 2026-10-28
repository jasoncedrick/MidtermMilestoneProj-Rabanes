# Barangay Recipes: annotated study version

Start here. Each source file now has comments explaining its purpose and the logic beside important blocks. Comments explain the existing behavior; application logic is unchanged.

## Clickable study order

| Step | Open file | What to understand |
|---|---|---|
| 1 | [database.sql](database.sql) | Six tables, foreign keys, category seeds and timestamps |
| 2 | [config/database.php](config/database.php) | One PDO connection, prepared statements and UTC |
| 3 | [includes/functions.php](includes/functions.php) | Sessions, login guard, CSRF, escaping, validation and redirects |
| 4 | [classes/User.php](classes/User.php) | Password hashing and verification |
| 5 | [register.php](register.php) / [login.php](login.php) / [logout.php](logout.php) | Account request flow |
| 6 | [classes/Recipe.php](classes/Recipe.php) | Search SQL, joins, transactions, ownership and favorites |
| 7 | [index.php](index.php) | GET filters and rendering recipe cards |
| 8 | [recipe_form.php](recipe_form.php) | One form for create/edit, PHP validation and ingredient arrays |
| 9 | [recipe.php](recipe.php) | Recipe details, comments, owner controls and Edited markers |
| 10 | [classes/Comment.php](classes/Comment.php) / [edit_comment.php](edit_comment.php) | Comment CRUD and ownership |
| 11 | [delete_recipe.php](delete_recipe.php) | POST-only deletion and cascading dependent rows |
| 12 | [assets/script.js](assets/script.js) / [favorite.php](favorite.php) | Browser interactions, fetch and JSON |
| 13 | [includes/header.php](includes/header.php) / [includes/footer.php](includes/footer.php) | Shared layout, navigation, flash messages and feedback |
| 14 | [assets/style.css](assets/style.css) / [.htaccess](.htaccess) | Presentation and Apache access rules |
| 15 | [kusina_barangay.sql](kusina_barangay.sql) | Existing-data snapshot, distinct from clean installation |

Use GitHub's file tree or these relative links to move around this branch. Use your browser Back button to return here. For PHP, select **Code** to read comments; **Raw** shows the complete source without GitHub formatting. These GitHub file links display source, not a running PHP website.

## Run locally and navigate the app

1. Use PHP 8.0 or newer with PDO MySQL and mbstring enabled, plus MySQL/MariaDB. XAMPP is one option.
2. Download or clone this branch into your web server's document root, for example `htdocs/MidtermMilestoneProj-Rabanes` in XAMPP.
3. Start Apache and MySQL. Import **database.sql** through phpMyAdmin for a clean installation with six categories and no accounts/recipes.
4. Check the host, database name and credentials in [config/database.php](config/database.php).
5. Open `http://localhost/MidtermMilestoneProj-Rabanes/register.php`, create an account, and you will be signed in.
6. Use **Share recipe** to create a recipe, **Recipes** to browse/search, **Favorites** for saved recipes, and **My recipes** for your own posts.
7. Open a recipe title to read details or comment. Owners see **Edit recipe**, **Delete recipe**, and edit/delete controls on their own comments.
8. **Log out** submits a protected POST and returns to login.

Alternatively, import [kusina_barangay.sql](kusina_barangay.sql) into a fresh database to restore its snapshot: six categories, four users, one recipe and two ingredients. It has no inserted comments/favorites. It contains password hashes, not documented plaintext passwords; create a new account if you do not know an existing password. Do not import both SQL files as though they were successive migrations.

## Follow a complete request

### Create or edit a recipe

`recipe_form.php` loads `includes/functions.php`, which starts the session and loads the database/models. `requireLogin()` blocks anonymous requests. A URL `id` selects edit mode and triggers an ownership check. POST checks the CSRF token, reads/normalizes fields and calls `validateRecipe()`. If there are errors, the page redisplays the submitted values and messages. Otherwise, `Recipe::save()` writes the recipe and ingredient rows in one transaction. Success commits and redirects to `recipe.php?id=...`; failure rolls back. The redirected GET prevents a browser refresh from repeating the save.

### Save a favorite

`assets/script.js` reads the button's recipe ID and current saved state. It sends the desired state and CSRF token to `favorite.php` using fetch. PHP checks login, POST, token, state and recipe existence, then calls `Recipe::setFavorite()`. A JSON response tells JavaScript whether the recipe is saved. JavaScript updates the button and, when unsaving in the Favorites view, removes its card and updates the count. This path does not reload the entire page.

### Post or edit a comment

`recipe.php` accepts `action=add_comment` or `action=delete_comment` on POST. Adding validates the body and inserts the session user's comment. Deleting restricts by comment ID, recipe ID and user ID. Editing goes through `edit_comment.php`, which checks ownership and length, then calls `Comment::update()`. Successful changes redirect back to the recipe's `#comments` section.

## Important concepts in this code

| Concept | Existing implementation |
|---|---|
| CRUD | INSERT creates, SELECT reads, UPDATE edits, DELETE removes |
| Authentication | Session user ID identifies a logged-in member |
| Authorization | Owner ID must match session user ID before modifications |
| Client-side validation | HTML constraints and script.js give browser feedback |
| Server-side validation | PHP repeats rules before calling save methods |
| SQL injection protection | Prepared statements bind values separately from SQL |
| Safe HTML output | e() uses htmlspecialchars on dynamic text |
| CSRF protection | Session token is included with modifying requests and checked by PHP |
| Transaction | Recipe and ingredient changes commit together or roll back together |
| JOIN | Load author/category names alongside recipe or comment records |
| EXISTS | Determine whether this user has saved a recipe |
| Flash message | Session message displayed once by header.php |
| POST/Redirect/GET | Successful regular form submission redirects before displaying the result |
| Foreign key | Link child records to existing parent records |
| Cascade deletion | Deleting a recipe removes its ingredients, comments and favorite links |
| Composite key | favorites uses (user_id, recipe_id) to prevent duplicate saves |

### Tables and relationships

| Table | Meaning | Main relationships |
|---|---|---|
| users | Accounts and password hashes | One user has many recipes/comments/favorites |
| categories | Six starting recipe categories | One category has many recipes |
| recipes | Recipe title, description, steps and cook time | Belongs to one user and category |
| ingredients | Ingredient text and position | Belongs to one recipe |
| comments | Body, author and timestamps | Belongs to one user and recipe |
| favorites | Saved recipe relationships | Connects users to recipes |

### Validation rules

Recipe validation requires title 3–100 characters, description 10–300, a real category, cooking time 1–1,440 minutes, 1–40 ingredients of 2–160 characters each, and steps 20–10,000 characters. Comments require 2–1,000 characters. Registration checks name 2–60 characters, a valid email up to 190 bytes, password at least 8 characters and at most 72 bytes, no null byte, matching confirmation, and email uniqueness. Browser rules can be bypassed; PHP determines whether a submitted form can proceed.

### Edited marker and ordering

`updated_at` starts NULL. Saving an edit sets it to CURRENT_TIMESTAMP, even when the submitted content is unchanged. Templates display `Edited` when that field has a value. The displayed date and newest-first ordering still use `created_at`; there is no version-history table. The recipe in the exported SQL snapshot already has an update timestamp, so its marker appears immediately after import.

### OOP in this project

User, Recipe and Comment encapsulate SQL methods and keep PDO in a private property. Constructors receive the shared PDO connection, an example of dependency injection. Pages call these objects rather than duplicating the SQL everywhere. Pages still combine request handling with HTML, so this is a small app with separated model classes, not a fully separated MVC framework.
