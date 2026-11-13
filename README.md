# FooGallery Update Recovery

A small, temporary WordPress plugin from FooPlugins that helps recover failed
FooGallery and FooBox updates showing **“Download failed. Found”**.

**[Download version 1.0.0](https://github.com/fooplugins/foo-update-recovery/releases/download/v1.0.0/foogallery-update-recovery.zip)**
· [Release notes and checksum](https://github.com/fooplugins/foo-update-recovery/releases/tag/v1.0.0)

Download the attached **foogallery-update-recovery.zip** asset. GitHub's
automatically generated “Source code” archives are repository snapshots, not
the installable recovery ZIP.

## When to use it

Some affected FooGallery versions block the redirects used to download plugin
updates from Freemius. Because that restriction applies to the shared download
service, it can also prevent FooBox updates on the same site. This helper enables
the supported update downloads while keeping the original security protections.

Use it if FooPlugins support recommends it or your FooGallery/FooBox update
fails with **“Download failed. Found”** while an affected FooGallery is active.
It is not a general solution for every failed WordPress update.

**This release is the recovery helper, not the permanent FooGallery fix.**
It does not install or make a new FooGallery release available. Confirm the
permanently fixed FooGallery version with FooPlugins support before removing
the helper. An update completing successfully does not, by itself, mean that
the installed FooGallery includes the permanent fix.

## Requirements

- WordPress **6.9 or newer** and PHP **7.2 or newer**.
- Permission to install and update plugins on the site.
- Your normal valid license/update entitlement for paid plugins.

The helper itself is free and needs no license key or configuration.

## Install and use

1. **Back up your site**, including its database and plugin files.
2. Download **foogallery-update-recovery.zip** using the link above. Keep it zipped.
3. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
4. Select the ZIP, click **Install Now**, then **Activate Plugin**.
5. Leave FooGallery and FooBox installed. Retry the failed update from your
   normal WordPress **Plugins** or **Dashboard → Updates** screen.
6. Check your galleries, saved settings and plugin license status after updating.

Do not delete FooGallery or replace a PRO edition with Free as part of this
process. The helper does not change your gallery content, settings or license.

## When to remove it

Once you have installed and activated a FooGallery release that FooPlugins
confirms contains the permanent redirect fix:

1. Open **Plugins → Installed Plugins**.
2. **Deactivate**, then **Delete**, **FooGallery Update Recovery**.
3. Check your galleries and verify the next available update works without it.
   Check FooBox too, if installed.

If no confirmed fixed FooGallery release is available yet, this helper remains
a temporary workaround while active. Keep it active until the permanent fix is
installed or support gives you another recovery route. Removing it while an
affected FooGallery version is still active can bring the error back.

The helper has no automatic self-update mechanism. Follow FooPlugins support
guidance and remove it after recovery rather than keeping it permanently.

## If the update still fails

- Check that the helper is active and that your paid-plugin license is valid.
- Confirm the update is actually available to your installation.
- Contact FooPlugins through your usual support channel with the error text,
  plugin versions, WordPress version and PHP version.
- Share account details and logs privately with support. **Do not post license
  keys, credentials, signed download links or customer data in GitHub issues.**

This helper cannot repair invalid licenses, unavailable releases, blocked
outbound connections or TLS/certificate failures. Do not disable HTTPS checks.
Increasing PHP upload limits does not fix the reproduced redirect problem,
although upload limits can separately affect manual ZIP installation.

An alternative is to obtain the verified fixed ZIP for your exact FooGallery
edition from FooPlugins, upload it through WordPress, and choose **Replace
current with uploaded**. Do not uninstall FooGallery first.

## What the helper does

- Handles recognized Freemius plugin/theme package downloads, including
  FooGallery and FooBox, through WordPress's normal updater.
- Follows up to three validated HTTPS redirects with TLS verification enabled.
- Does not forward authentication headers or cookies between download hops,
  or append the original authorization query to the redirect destination.
- Rejects unsafe destinations, redirect loops and excessive redirect chains.
- Leaves ordinary API transport protection and normal license checks in place.
- Adds no settings, accounts or telemetry. Activation itself makes no network
  requests; downloads happen only when the normal updater requests a package.

The release ZIP is the exact artifact used in isolated recovery testing:
an affected FooGallery updated successfully with the helper, and subsequent
downloads worked after installing the permanent code fix and removing it.
The shared downloader was also exercised with FooBox single, bulk and automatic
updates. These tests used controlled HTTPS downloads, not customer credentials.

## Verify the download

The release includes **foogallery-update-recovery.zip.sha256**. Version 1.0.0's
ZIP is 4,183 bytes with SHA-256:

```text
73397b44bebbc73b6ce460d30a9aac6a6b6108c4f5e92dbbeecbe61cd9597cb3
```

## Build from source

Requires Python 3. Run from the repository root:

```bash
python3 tools/build-release.py
```

This creates the installable ZIP and checksum under `dist/`. The builder includes
only the three files in `foogallery-update-recovery/`, with fixed ZIP metadata.
The repository README and other development files are not installed on the site.

## License

The plugin is licensed under **GPL-2.0-or-later**, as declared in its header.
See [GPL version 2](LICENSE-2.0.txt) and [GPL version 3](LICENSE).
