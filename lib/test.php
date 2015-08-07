<html>
<body>
<pre>

<?php

include 'backend.php';

//
// Users
//
$users = new SimpleUserManager("../database/users.sqlite");

// Session id tests
print "good user:    " . $users->getUser("sessionid:" . intval(time() / 60))      . "\n";
print "expired user: " . $users->getUser("sessionid:" . intval(time() / 60 - 65)) . "\n";

// Login tests
$users->changePassword("test","fark") . "\n";
print "add:          " . $users->addUser("test","fark") . "\n";
print "good login:   " . $users->getSessionId("test",   "fark")  . "\n";
print "bad user:     " . $users->getSessionId("nobody", "fark")  . "\n";
print "bad password: " . $users->getSessionId("test",   "fark1") . "\n";
$users->changePassword("test","fark1") . "\n";
print "new password: " . $users->getSessionId("test",   "fark1") . "\n";
$users->changePassword("test","fark") . "\n";

$sid  = $users->getSessionId("test", "fark");
$user = $users->getUser($sid);

//
// Catalog
//
print "--------Catalog Test--------\n";
$catalog = new SimpleCatalog("../database/brainz.sqlite");

print "\n--Track Browse\n";
$results = $catalog->browseTrack("bcbaca8b-9480-4041-b401-17c209d63dca"); 
foreach ($results['data'] as $result) { 
    $id       = $result['id']; 
    $album    = $result['album']; 
    $artist   = $result['artist'];
    $index    = $result['idx']; 
    $title    = $result['title'];
    $duration = $result['duration'];
    print "$id: $index: $album: $artist: $title: $duration\n"; 
} 

print "\n--Staff favorites\n";
$results = $catalog->browsestaffFavorites(0,1000);
if ($results['total']) {
    foreach ($results['data'] as $result) { 
        $id     = $result['id']; 
        $artist = $result['name']; 
        print "$id: $artist\n"; 
    }
}

print "\n--Artist search\n";
$results = $catalog->searchArtist('e', 0, 25);
$index = $results['index'];
$total = $results['total'];
print "i: $index, t: $total\n";
foreach ($results['data'] as $result) {
    $id    = $result['id'];
    $name  = $result['name'];
    print "$id : $name\n";
}

print "\nAlbum search\n";
$results = $catalog->searchAlbum('e', 0, 25);
foreach ($results['data'] as $result) {
    $id     = $result['id'];
    $album  = $result['title'];
    $artist = $result['artist'];
    print "$id: $album: $artist\n";
}

print "\nTrack search\n";
$results = $catalog->searchTrack('e', 0, 25); 
foreach ($results['data'] as $result) { 
    $id       = $result['id']; 
    $artist   = $result['artist'];
    $artistid = $result['artistid'];
    $album    = $result['album']; 
    print "$id : $artist : $album : $artistid\n"; 
} 

print "\nTrack search2\n";
$results = $catalog->searchTrack('e', 10, 25); 
foreach ($results['data'] as $result) { 
    $id       = $result['id']; 
    $artist   = $result['artist'];
    $artistid = $result['artistid'];
    $album    = $result['album']; 
    print "$id : $artist : $album : $artistid\n"; 
} 

print "\nArtist browse\n";
$results = $catalog->browseArtist("65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab",0,1000); 
foreach ($results['data'] as $result) { 
    $id     = $result['id']; 
    $album  = $result['title']; 
    $artist = $result['artist']; 
    print "$id: $album: $artist\n"; 
} 

print "\nAlbum browse\n";
$results = $catalog->browseAlbum("da53e497-8c61-4d4a-a29b-a5b53d86ccb7",0,1000); 
foreach ($results['data'] as $result) { 
    $id       = $result['id']; 
    $album    = $result['album']; 
    $artist   = $result['artist'];
    $index    = $result['idx']; 
    $title    = $result['title'];
    $duration = $result['duration'];

    print "$id: $index: $album: $artist: $title: $duration\n"; 
} 

print "\nArtist info\n";
$result = $catalog->getArtistInfo("65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab");
$id     = $result['id'];
$name   = $result['name'];
print "$id: $name\n";

print "\nAlbum info\n";
$result = $catalog->getAlbumInfo("da53e497-8c61-4d4a-a29b-a5b53d86ccb7");
$id         = $result['id'];
$title      = $result['title'];
$artistid   = $result['artistid'];
$artistname = $result['artist'];
print "$id: $title: $artistid ($artist)\n";

print "\nTrack info\n";
$result = $catalog->getTrackInfo("bcbaca8b-9480-4041-b401-17c209d63dca");
$id         = $result['id'];
$title      = $result['title'];
$albumid    = $result['albumid'];
$albumname  = $result['album'];
$artistid   = $result['artistid'];
$artistname = $result['artist'];
print "$id: $title: $artistid ($artist): $albumid ($album)\n";

//
// Favorites
//
$favs = new SimpleFavorites("../database/favorites.sqlite");

$favs->addFavorite("albums", $user, "fake_gid_001");
$favs->addFavorite("albums", $user, "fake_gid_001");
$favs->addFavorite("albums", $user, "fake_gid_001");

$ids = $favs->getFavorites("albums", $user);
foreach ($ids as $id) {
    print "fav: $id\n";
}

$favs->delFavorite("albums", $user, "fake_gid_001");

//
// Ratings
//
print "\n--------Ratings Tests--------\n";
$ratings = new SimpleRatings("../database/ratings.sqlite");

$ratings->addRating($user, "fake_gid_001", 5);
$ratings->addRating($user, "fake_gid_002", 3);
$ratings->addRating($user, "another_fake_gid_003", 1);
// check if they're added correctly
$ratingsArr = $ratings->getRatingsForUser($user);
foreach ($ratingsArr as $rating) {
    print "Rating: ".$rating['rating'].", GID: ".$rating['gid']."\n";
}
print "\n--Modifying ratings\n";
$ratings->addRating($user, "fake_gid_002", 1);
$ratings->addRating($user, "another_fake_gid_003", -1);
$ratingsArr = $ratings->getRatingsForUser($user);
foreach ($ratingsArr as $rating) {
    print "Rating: ".$rating['rating'].", GID: ".$rating['gid']."\n";
}

print "\n--Deleting ratings\n";
$ratings->addRating($user, "fake_gid_002", 0);
$ratings->delRating($user, "another_fake_gid_003");
$ratingsArr = $ratings->getRatingsForUser($user);
foreach ($ratingsArr as $rating) {
    print "Rating: ".$rating['rating'].", GID: ".$rating['gid']."\n";
}

print "\n--Getting ratings\n";
print "Rating: ".$ratings->getRating($user,"fake_gid_001")."\n";
?>

</pre>
</body>
</html>
