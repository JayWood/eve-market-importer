# Eve Online Market Importer ( Alpha )

This WordPress Plugin adds [WP_CLI](http://wp-cli.org/) commands for importing data from the EvE Crest API
and adds custom post types and taxonomies where necessary.  In-turn, allowing any WordPress geek
full control over the data.  Sure! You can do this with the static data dump, but where's the fun in that?

## Current CLI Commands
### groups 
`wpcli eve-market-import groups`

**Flags**

* `verbose` - Floods your CLI with information ( usually not needed )   
* `warn` - Will ignore errors and continue ( not suggested unless you know what you're doing )   

This command imports market groups to the market groups taxonomy in a hierarchical fashion, as seen on EvEOnline.  To do this,
it uses the [market/groups](http://public-crest.eveonline.com/market/groups/) endpoint, re-formats the data into a prettier
format, [like this](https://gist.github.com/JayWood/7f6025d4feea193ae3f3), and walks through the array importing each and every one, one by one.

**This command DOES NOT allow for re-import, so it's not really safe to run it more than once.  I will be updating it to do so in the near future.**

### types 
`wpcli eve-market-import types`

**Flags**

* `verbose` - Floods your CLI with information ( usually not needed )   
* `warn` - Will ignore errors and continue ( not suggested unless you know what you're doing )
* `live` - Actually imports data.

This command imports market types ( items ) in as posts and links the appropriate categories ( market groups ) to them where necessary.  All groups that
are associated with them are assigned. **see screenshot 2**  This command is intelligent, and should iterate over EVERY page available, in-turn, importing
every market item as a post type.  Due to the time it takes to import just a single page ( 1000 items ), I don't see this script hitting the limits CCP has
on un-auth'd API requests.

**This command DOES NOT allow for re-import, so it's not really safe to run it more than once.  I will be updating it to do so in the near future.**

## Screenshots
![Nested Market Groups](https://raw.githubusercontent.com/JayWood/eve-market-importer/master/screenshot-1.jpg)   
Nested Market Groups

![Imported Item](https://raw.githubusercontent.com/JayWood/eve-market-importer/master/screenshot-2.jpg)   
Imported Item w/ Assigned Groups