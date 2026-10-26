# Bundled WXR importer

These three files are extracted from Webilia's existing Demo Import package and derive from
[Awesome Motive's WordPress Importer](https://github.com/awesomemotive/WordPress-Importer),
which derives from [Human Made's WordPress Importer](https://github.com/humanmade/WordPress-Importer).

Authors include Gregor Capuder, Primoz Cigler, Ryan McCue and Human Made contributors;
the original WordPress Importer credits Ryan Boren, Jon Cave, Andrew Nacin and Peter Westwood.
The importer is GPL-2.0-or-later (see LICENSE). It is not covered by the MIT license of the
surrounding Webilia utilities. This extraction changes its PHP namespace to
`Webilia\WP\DemoImport\Importer` to avoid legacy autoloader collisions.

The extracted importer also validates complete WXR XML before starting WordPress import mutations.
