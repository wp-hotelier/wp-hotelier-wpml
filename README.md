# WP Hotelier Multilingual

This extension lets you run a multilingual website with the [WP Hotelier - WordPress Booking Plugin](https://wphotelier.com/) and WPML. This is a glue plugin, that uses hooks in both WP Hotelier and [WPML](https://wpml.org/).

## Scope of this plugin

This a very basic and **not supported** integration between WP Hotelier and WPML. The main function of this plugin is the synchronization of the stock of the rooms between different languages. WPML creates a new room for each translation (with a different ID), so we must have a function that synchronizes the stock.

The extension includes also a ready to use language configuration file for WPML. But again, support is not provided for this plugin (or for WPML in general). You are free to extend or build a most complete solution.

## WP Hotelier and WPML: the workflow

The first step is read the [WPML documentation](https://wpml.org/documentation/).

WP Hotelier requires [two pages to work](http://docs.wphotelier.com/settings.html#hotelier-pages), the *listing* page and the *booking* page. You must create a translation for each of those pages and include the `[hotelier_listing]` and the `[hotelier_booking]` shortcodes (respectively) in the translated pages. In *Hotelier > Settings > Hotelier Pages* you can always check if the required pages were created and set correctly.

Translate your room rates, room categories and room facilities. Those are simple taxonomies, so you can follow [this guide](https://wpml.org/documentation/getting-started-guide/translating-post-categories-and-custom-taxonomies/).

**Important**: You must translate your room rates before to translate a room with variations.

To translate a room, you just need to follow the same steps required to translate a normal post. In short, click the “+” icon for the language you want to translate into:

![WPML translate icon](https://d2salfytceyqoe.cloudfront.net/wp-content/uploads/2011/01/wpml-add-translation.png)

The idea is to create an exact copy of the room in each translation. Same price, rates and settings. That's because is the original room (the room created in the default language) the one is added to the cart, regardless of the language selected by the guest. So, each translation must have the same features.

The easiest way to do this is clicking on the **"Overwrite with English content"** button (replace *English* with your default language):

![WPML overwrite button](https://d2salfytceyqoe.cloudfront.net/wp-content/uploads/2011/01/wpml-translating-a-post.png)

This will sync the content of the translation with the content of the room created with the default language. You don't want to sync the content in this way forever, otherwise you will not be able to modify each translation independently. That's only a quick and temporary way to populate the translation with the same settings of the default room. After the sync, click on the **"Translate independently"** button to make this translation independent again and save the room.

If the room is a variable room, assign the correct rate to each variation. Unfortunately rate names are not synchronized correctly, so you need to re-assign the correct rate in the select dropdown.

**VERY IMPORTANT**: Variable rooms must have the **same variations** in the **same order** in each language!

## Conclusion

That's it. If you need help with WPML contact his authors directly. Again, no support is provided for this extension (or for WPML).
