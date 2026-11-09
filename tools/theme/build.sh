#!/usr/bin/env sh
# Rebuilds the white + dark green + black theme override stylesheets from the original CSS.
# Run from the project root:  sh tools/theme/build.sh
# Edit tools/theme/*-extra.css for hand-written tweaks, then rebuild.
set -e
D=tools/theme
F=public/frontend/css
B=public/backend/assets

php $D/generate.php $F/theme.css $D/frontend-extra.css \
  $F/bootstrap.min.css $F/select2.min.css $F/slick.css $F/jquery.nice-number.min.css \
  $F/jquery.calendar.css $F/add_row_custon.css $F/mobile_menu.css $F/jquery.exzoom.css \
  $F/multiple-image-video.css $F/ranger_style.css $F/jquery.classycountdown.css \
  $F/venobox.min.css $F/style.css $F/responsive.css

php $D/generate.php $B/css/theme.css $D/backend-extra.css \
  $B/modules/bootstrap/css/bootstrap.min.css $B/modules/summernote/summernote-bs4.css \
  $B/modules/bootstrap-daterangepicker/daterangepicker.css $B/modules/select2/dist/css/select2.min.css \
  $B/css/bootstrap-iconpicker.min.css $B/css/style.css $B/css/components.css
