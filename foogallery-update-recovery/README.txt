Temporary FooGallery update recovery

Install only a build supplied and approved by FooPlugins support.
Back up the site, upload this ZIP through Plugins > Add New > Upload Plugin,
and activate it. Leave the existing FooGallery and FooBox plugins installed.
Run the normal FooGallery update to a verified fixed release, then deactivate
and delete this recovery helper. Verify the next update without the helper.

This plugin preserves TLS verification and the original no-redirect API guard.
It only handles recognized Freemius package URLs through WordPress's updater.
It does not change a license, collect data, or modify saved galleries/settings.
It cannot solve invalid licenses, inaccessible servers, or unavailable releases.
Do not remove it after installing another affected version and assume the issue
is fixed. The current source version number alone is not a release approval.
