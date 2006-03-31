<?php

# # Wrapper for bitcollider file metadata extraction tool.
# #
# #   http://bitzi.com/bitcollider/
# #
# # Does not interact with Bitzi web services.
#
# $b = new Bitcollider();
#
# # Normally bitcollider doesn't calculate md5
# $b->set_calculate_md5(true);
# $b->analyze_file('/path/to/my/test.txt');
# echo($b->get_sha1());
# echo($b->get_md5());
#
# # There are many format-specific getters.  Because
# # we're looking at a text file, the following will
# # print nothing:
# echo($b->getmp3bitrate());
#
# # These utility functions generate MAGNET links
# #
# #   http://magnet-uri.sourceforge.net/
# #
# echo($b->getmagnetlink());
#
# # A magnet link can optionally include a direct
# # download file URL in addition to file hashes
# echo($b->getmagnetlink('http://example.com/test.txt));
#
# # A standalone magnetlink() function is included in this
# # file.  Useful if you already have hashes in a database.
# echo(magnetlink($sha1, $filename, $fileurl, $treetiger, $kzhash));

class Bitcollider {
  var $BITCOLLIDER = '';
  function set_program_location($bitcollider_full_path) {
    $this->BITCOLLIDER = $bitcollider_full_path;
  }
  var $file_metadata;
  var $calculate_md5 = false;
  function set_calculate_md5($boolean) { $this->calculate_md5 = $boolean; }
  var $calculate_crc32 = false;
  function set_calculate_crc32($boolean) { $this->calculate_crc32 = $boolean; }
  function analyze_file($filename) {
    $filename = escapeshellarg($filename);
    $cmd = "$this->BITCOLLIDER -p";
    if ($this->calculate_md5) { $cmd .= " --md5"; }
    if ($this->calculate_crc32) { $cmd .= " --crc32"; }
    $cmd .= " $filename";
    exec($cmd, $out, $return_code);
    $this->file_metadata = array();
    foreach($out as $line) {
      if (preg_match('/^(\S+?)=(.*)$/',$line,$matches)) {
        $key = $matches[1];
        $value = $matches[2];
        $this->file_metadata[$key] = $value;
      }
    }
  }
  function get_magnetlink($file_url='') {
    return magnetlink($this->get_sha1(), $this->get_filename(), $file_url, $this->get_treetiger(), $this->get_kzhash());
  }
  function get_sha1() {
    $a = $this->get_sha1_treetiger();
    return $a[0];
  }
  function get_treetiger() {
    $a = $this->get_sha1_treetiger();
    return $a[1];
  }
  function get_bitprint() { return $this->file_metadata['bitprint']; }
  function get_md5() { return $this->file_metadata['tag.md5.md5']; }
  function get_ed2k() { return $this->file_metadata['tag.ed2k.ed2khash']; }
  function get_kzhash() { return $this->file_metadata['tag.kzhash.kzhash']; }
  function get_crc32() { return $this->file_metadata['tag.crc32.crc32']; }
  function get_filename() { return $this->file_metadata['tag.filename.filename']; }
  function get_filelength() { return $this->file_metadata['tag.file.length']; }
  function get_first20bytes() { return $this->file_metadata['tag.file.first20']; }
  function get_audiotracktitle() { return $this->file_metadata['tag.audiotrack.title']; }
  function get_audiotrackartist() { return $this->file_metadata['tag.audiotrack.artist']; }
  function get_audiotrackalbum() { return $this->file_metadata['tag.audiotrack.album']; }
  function get_audiotracknumber() { return $this->file_metadata['tag.audiotrack.tracknumber']; }
  function get_audiotrackyear() { return $this->file_metadata['tag.audiotrack.year']; }
  function get_vorbisbitrate() { return $this->file_metadata['tag.vorbis.bitrate']; }
  function get_vorbisduration() { return $this->file_metadata['tag.vorbis.duration']; }
  function get_vorbissamplerate() { return $this->file_metadata['tag.vorbis.samplerate']; }
  function get_vorbischannels() { return $this->file_metadata['tag.vorbis.channels']; }
  function get_vorbisencoder() { return $this->file_metadata['tag.vorbis.encoder']; }
  function get_vorbisaudiosha1() { return $this->file_metadata['tag.vorbis.audio_sha1']; }
  function get_mp3bitrate() { return $this->file_metadata['tag.mp3.bitrate']; }
  function get_mp3vbr() { return $this->file_metadata['tag.mp3.vbr']; }
  function get_mp3duration() { return $this->file_metadata['tag.mp3.duration']; }
  function get_mp3stereo() { return $this->file_metadata['tag.mp3.stereo']; }
  function get_mp3encoder() { return $this->file_metadata['tag.mp3.encoder']; }
  function get_mp3audiosha1() { return $this->file_metadata['tag.mp3.audio_sha1']; }
  function get_imageformat() { return $this->file_metadata['tag.image.format']; }
  function get_imagewidth() { return $this->file_metadata['tag.image.width']; }
  function get_imageheight() { return $this->file_metadata['tag.image.height']; }
  function get_imagebpp() { return $this->file_metadata['tag.image.bpp']; }
  function get_id3genre() { return $this->file_metadata['tag.id3genre.genre']; }
  function get_wavsamplesize() { return $this->file_metadata['tag.wav.samplesize']; }
  function get_wavduration() { return $this->file_metadata['tag.wav.duration']; }
  function get_wavsamplerate() { return $this->file_metadata['tag.wav.samplerate']; }
  function get_wavchannels() { return $this->file_metadata['tag.wav.channels']; }
  function get_wavaudiosha1() { return $this->file_metadata['tag.wav.audio_sha1']; }
  function get_videoformat() { return $this->file_metadata['tag.video.format']; }
  function get_videowidth() { return $this->file_metadata['tag.video.width']; }
  function get_videoheight() { return $this->file_metadata['tag.video.height']; }
  function get_videofps() { return $this->file_metadata['tag.video.fps']; }
  function get_videoduration() { return $this->file_metadata['tag.video.duration']; }
  function get_videobitrate() { return $this->file_metadata['tag.video.bitrate']; }
  function get_videocodec() { return $this->file_metadata['tag.video.codec']; }
  function get_sha1_treetiger() {
    if (preg_match('/([A-Za-z2-7]{32})\.([A-Za-z2-7]{39})/',$this->get_bitprint(),$matches)) {
      return array($matches[1],$matches[2]);
    }
    return array('','');
  }
}

function magnetlink($sha1, $filename, $fileurl, $treetiger, $kzhash) {
  $m = "";
  if ($sha1 && $treetiger) {
    $m .= "xt=urn:bitprint:$sha1.$treetiger";
  } else {
    if ($sha1) {
      $m .= "xt=urn:sha1:$sha1";
    } else if ($treetiger) {
      $m .= "xt=urn:tree:tiger:$treetiger";
    }
  }
  if ($kzhash) {
    if ($m) { $m .= "&"; }
    $m .= "xt=urn:kzhash:$kzhash";
  }
  if ($filename) {
    if ($m) { $m .= "&"; }
    $filename = urlencode($filename);
    $m .= "dn=$filename";
  }
  if ($fileurl) {
    if ($m) { $m .= "&"; }
    $fileurl = urlencode($fileurl);
    $m .= "xs=$fileurl";
  }
  return "magnet:?$m";
}

?>
