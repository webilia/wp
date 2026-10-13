<?php

namespace Webilia\WP;

/** Result-aware extension, including when an older Announcements class was loaded first. */
class AnnouncementClient extends Announcements
{
    use AnnouncementFetch;
}
