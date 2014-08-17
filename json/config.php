<?php

/** Minimal Configuration */

/** Your Teamspeak server hostname or ip */
$ts_host = 'server_hostname_or_ip';

/** Server's client port, not the query port! (default 9987) */
$ts_port = 9987;

/** Server's query port, not the client port! (default 10011) */
$ts_query_port = 1011;

/** The timeout, in seconds, for connect, read, write operations (default 2) */
$ts_timeout = 2;

/** Optional Settings */

/** [Optional] The server query's login */
$ts_query_login = '';

/** [Optional] The server query's password */
$ts_query_password = '';

/** [Optional] Cache data for X seconds before updating (prevent bans from the server). 0 => disabled (default 120) */
$ts_cache = 120;

/** [Optional] Comma seperated list of channel IDs to display. If set, only these channels will be returned */
$ts_channel_id_whitelist = '';

/** [Optional] Hide empty channels (default false) */
$ts_hide_empty_channels = false;

/** [Optional] Hide parent of filtered channels (non-whitelist channels or empty channels) (default false) */
$ts_hide_parent_channels = false;

?>