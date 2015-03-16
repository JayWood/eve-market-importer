# Eve Online Market Importer ( Alpha )

This WordPress Plugin adds [WP_CLI](http://wp-cli.org/) commands for importing data from the EvE Crest API
and adds custom post types and taxonomies where necessary.  In-turn, allowing any WordPress geek
full control over the data.

## Current CLI Commands
### groups 
`wpcli eve-market-import groups`

**Flags**

* `verbose` - Floods your CLI with information ( usually not needed )   
* `warn` - Will ignore errors and continue ( not suggested unless you know what you're doing )   

This command imports market groups to the market groups taxonomy in a hierarchical fashion, as seen on EvEOnline.  To do this,
it uses the [market/groups](http://public-crest.eveonline.com/market/groups/) endpoint, re-formats the data into a prettier
format, [like this](https://gist.github.com/JayWood/7f6025d4feea193ae3f3), and walks through the array importing each and every one, one by one.

**This command DOES NOT re-import the groups, so it's not really safe to run it more than once.  I will be updating it to do so in the near future.**

## Screenshots
![Nested Market Groups](https://raw.githubusercontent.com/JayWood/eve-market-importer/master/screenshot-1.jpg)   
Nested Market Groups