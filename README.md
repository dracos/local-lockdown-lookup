# local-lockdown-lookup
A postcode lookup for UK local coronavirus lockdowns.

Does what it says on the tin.

* index.php does the actual postcode/point lookup and working out of results.
* utils.php prints it out, and has functions for loading the data (caching it as PHP), validating input, and so on.
* areas.csv provides the data, each row is a MapIt ID, a URL for more details, and a start date if needed.

The code handles all of the UK, overseas territory postcodes, imaginary postcodes, and Crown dependencies.

License: GNU Affero General Public License
