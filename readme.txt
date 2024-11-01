=== Unique Coupons ===

Contributors: josefwittmann
Tags: coupon, coupons, unique
Requires at least: 5.3
Tested up to: 5.7
Requires PHP: 7.1
Stable tag: 0.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Distribute unique coupons to your users.

== Description ==

Allows you to upload a set of coupon codes you want to distribute to your users. Each coupon is assumed to be used only once.

You can manage multiple sets of coupons from different sources.

Your users are shown a popup next time they visit the site. They have are only shown the coupon after clicking a button, to ensure interest.

= Usage =

After installing, you'll find a new menu item in the admin area near the bottom called 'Coupons'. All administration is done from there.

There are _coupons_ and _(coupon) groups_.
Each coupon belongs to exactly one group and has a value and expiry date.
A group contains many coupons and defines the template that is shown to the users within a popup. By default, only logged in users can get coupons.

Start by creating a new group. Give it an unique name and write out the template.
There are four buttons in the editor, which mark the speical areas for the popup. Highlight the according text and press the button to mark it up.

-   **Action button**: When this button is clicked, the coupon's value will be fetched from the backend and displayed.
-   **Success area**: This area is hidden until the coupon is fetched from the backend.
-   **Coupon value**: This area will be _replaced_ with the coupon's value. Make sure to not include trailing whitespace, otherwise it may look ugly.
-   **Expiry date**: This area will be _replaced_ with the coupon's expiry date. Make sure to not include trailing whitespace, otherwise it may look ugly.

Save the group and add some coupons. You can add multiple coupons with the same expiry date. Just make sure that every coupon gets its own line (empty lines are ignored).

Now you're ready to go. But you may want to have a look at the default settings. There you can adjust delays between events to not spam your users.

= Customization =

Most of the user-facing customization can be done in the group editor. If you want to change the users which should be able to get coupons, you can hook into the `unique_coupons_user_is_authorized_for_coupons` filter. Currently, there is no way to distribute coupons to anonymous users (keeping track is done server-side). So even if you allow this through this filter, it will not work.

= Shortcomings/Bugs =

-   For now, data from this plugin will stay around after deleting it.
-   You can't explicitly filter when the popup shouldn't be shown. The best workaround is to hook into `unique_coupons_user_is_authorized_for_coupons`.

= Contributing =

The source code is hosted on [Josef37/unique-coupons](https://github.com/Josef37/unique-coupons).
Feel free to create a new issue, when you have questions or feature requests, or consider making a pull request.
There is a separate [developer README](https://github.com/Josef37/unique-coupons/blob/main/README_DEV.md), which helps you set up the development environment.

If this plugin helped you in any way, I'd like to hear your feedback.

== Installation ==

Install like any plugin (via wp-admin or uploading the plugin manually).

You'll find a new menu item in the admin area near the bottom called 'Coupons'. All administration is done from there.

== Screenshots ==

1. Popup before getting the coupon: Clicking the button will fetch the coupon from the server.
2. Popup after getting the coupon: The coupon got placed like defined in the template.
3. Admin page for a coupon group: Set name, active state, template and manage coupons.
4. Admin settings page.

== Changelog ==

= 0.1.3 =
- Add popup preview mode
- Avoid race condition between users: When the popup gets loaded, a coupon gets reserved for a user until he retrieves it, closes the popup or a timeout has elapsed.
- Record popup on open instead of page load

= 0.1.2 =
- Allow settings z-index for popup
- Add shortcode support for popup
- Add loading spinner when fetching coupon
- Allow popup elements to be undefined

= 0.1.1 =
- Show group-custom templates to users through a popup
- Only show the coupon after the user clicked a button on the popup
- Filter which users are allowed to get coupons
- Create groups with custom templates
- Add coupons to groups
- Bulk actions for managing coupons
- Settings for delays between events
