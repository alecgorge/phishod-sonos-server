<?php

//
// Small wrapper around sqlite.  Mostly here so I can share some code between
// the various classes that are backed by databases. Turns out that there isn't
// that much yet, sadly...
//
class SimpleDatabase
{
    private $db;
    private $path;
    
    public function __construct($path) {
        $this->db   = new PDO("sqlite:" . $path);
        $this->path = $path;
    }
    
    public function getUpdateTime() {
        return filemtime($this->path);
    }
    
    public function query($string) {
        // print "Q: " . $string . "\n";
        return $this->db->query($string);
    }
    
    public function prepare($string) {
        return $this->db->prepare($string);
    }
    
    public function exec($string) {
        return $this->db->exec($string);
    }

}

class SimpleCatalog extends SimpleDatabase
{
    function getLastUpdate() {
        return $this->getUpdateTime();
    }
    
    //
    // Simple lookups for extended metadata
    //
    function getArtistInfo($id)
    {
        $row = $this->query("SELECT " .
                            "gid as id, name as name " .
                            "FROM artist " .
                            "WHERE gid='$id' LIMIT 1;");
        
        $data = array();
        
        if ($row) {
            $data = $row->fetch();
            $data['artistid'] = $data['id'];
            $data['artist']   = $data['name'];
        }
        
        return $data;        
    }
    
    function getAlbumInfo($id)
    {
        $row = $this->query("SELECT " .
                            "album.gid as id, album.title as title, " .
                            "artist.name as artist, artist.gid as artistid " .
                            "FROM album " .
                            "INNER JOIN artist on album.artistid = artist.id " .
                            "WHERE album.gid='$id' LIMIT 1;");

        $data = array();
        
        if ($row) {
            $data = $row->fetch();
            $data['albumid'] = $data['id'];
            $data['album']   = $data['title'];
        }
        
        return $data;        
    }

    function getTrackInfo($id)
    {
        $row = $this->query("SELECT " .
                            "track.gid as id, track.title as title, " .
                            "album.gid as albumid, album.title as album, " .
                            "artist.gid as artistid, artist.name as artist " .
                            "FROM track " .
                            "INNER JOIN artist ON album.artistid = artist.id " .
                            "INNER JOIN album ON track.albumid = album.id " .
                            "WHERE track.gid='$id' LIMIT 1;");
        
        return $row ? $row->fetch() : array();
    }

    //
    // Complex lookups (albums for an artist, tracks for an album, etc)
    //
    // This returns a collection of albums
    function browseArtist($id, $offset, $limit)
    {
        return $this->queryCatalog("album",
                                   "artist.gid", $id,
                                   "album.title", // KLUDGE: Should be descending release date...
                                   "album.gid as id, album.title as title, artist.name as artist",
                                   "INNER JOIN artist ON album.artistid = artist.id",
                                   $offset,
                                   $limit);
    }

    // This returns a collection of tracks, and it is actually pretty painful
    // as it also has to look through albums.
    function browseAlbum($id, $offset, $limit)
    {
        return $this->queryCatalog("track",
                                   "album.gid", $id,
                                   "track.idx",
                                   "track.gid as id, " .
                                   "track.title as title, " .
                                   "album.title as album, " .
                                   "artist.name as artist, " .
                                   "track.duration as duration, " .
                                   "track.idx as idx ",
                                   "INNER JOIN artist ON album.artistid = artist.id " .
                                   "INNER JOIN album ON track.albumid = album.id ",
                                   $offset,
                                   $limit);
    }

    function browseTrack($id)
    {
        return $this->queryCatalog("track",
                                   "track.gid", $id,
                                   "",
                                   "track.gid as id, " .
                                   "track.title as title, " .
                                   "album.title as album, " .
                                   "album.gid as albumid, " .
                                   "artist.name as artist, " .
                                   "artist.gid as artistid, " .                                   
                                   "track.duration as duration, " .
                                   "track.idx as idx ",
                                   "INNER JOIN artist ON album.artistid = artist.id " .
                                   "INNER JOIN album ON track.albumid = album.id ",
                                   0,
                                   1);
    }

    function browseStaffFavorites($offset, $limit)
    {
        return $this->queryCatalog("staff",
                                   "", "",
                                   "artist.name",
                                   "artist.gid as id, artist.name as name",
                                   "INNER JOIN artist ON staff.artistid = artist.id",
                                   $offset,
                                   $limit);
    }
    
    //
    // Searches
    //
    function searchArtist($term, $offset, $limit)
    {
        return $this->queryCatalog("artist",
                                   "name", $term,
                                   "name",
                                   "gid as id, name",
                                   "",
                                   $offset,
                                   $limit);
    }

    function searchAlbum($term, $offset, $limit)
    {
        return $this->queryCatalog("album",
                                   "album.title", $term,
                                   "album.title",
                                   "album.gid as id, album.title as title, artist.name as artist",
                                   "INNER JOIN artist ON album.artistid = artist.id",
                                   $offset,
                                   $limit);
    }

    function searchTrack($term, $offset, $limit)
    {
        return $this->queryCatalog("track",
                                   "track.title", $term,
                                   "track.title",
                                   "track.gid as id, " .
                                   "track.title as title, " .
                                   "album.title as album, " .
                                   "album.gid as albumid, " .
                                   "artist.name as artist, " .
                                   "artist.gid as artistid",
                                   "INNER JOIN album ON track.albumid = album.id " .
                                   "INNER JOIN artist ON album.artistid = artist.id ",
                                   $offset,
                                   $limit);
    }
    

    //
    // All of the database queries go through here
    //   
    private function queryCatalog($table,
                                  $searchField, $searchTerm,
                                  $sortField,
                                  $returnedFields,
                                  $joins,
                                  $offset, $limit)
    {
        $result['index'] = $offset;
        $result['total'] = 0;

        //
        // Generate search term
        //
        $where = "";
        if ($searchField != "" && $searchTerm != "") {
            $where = "WHERE $searchField LIKE " . "'%" . $searchTerm . "%'";
        }

        //
        // Count matches
        //
        $query = "SELECT COUNT(1) FROM $table";
        
        if ($joins != "") {
            $query .= " $joins";
        }
        
        if ($where != "") {
            $query .= " $where";
        }
                    
        $tmp = $this->query("$query;");        
        $result['total'] = $tmp ? $tmp->fetchColumn() : 0;
        $result['data']  = array();
        
        //
        // Any point in doing the query?
        //        
        if ($offset < $result['total']) {
            
            $query = "SELECT $returnedFields FROM $table";
            
            if ($joins != "") {
                $query .= " $joins";
            }

            if ($where != "") {
                $query .= " $where";
            }

            if ($sortField != "") {
                $query .= " ORDER BY $sortField";
            }
            
            $query .= " LIMIT $limit OFFSET $offset";
            $rows   = $this->query("$query;");
            
            if ($rows) {
                $result['data'] = $rows->fetchAll();
            }
        }

        return $result;
    }    
}

class SimpleFavorites extends SimpleDatabase
{
    function getLastUpdate($user) {
        // NOTE: Currenetly returns last update for *any* user.  Close enough
        //       for what we're doing.
        return $this->getUpdateTime();
    }
    
    public function addFavorite($table, $usergid, $gid) {        
        // NOTE: This really needs a transaction to avoid races (specifically
        //       same exact favorite being added for the same exact user at the
        //       same time can cause duplicates).  This is OK(ish) for a low
        //       performance sample app though.
        //
        $result = $this->query("SELECT COUNT(1) FROM $table WHERE user='$usergid' AND gid='$gid';");

        $count = $result ? $result->fetchColumn() : 0;
        print "count: $count\n";
        if ($count == 0) {
            $this->exec("INSERT INTO $table(user,gid) VALUES('$usergid','$gid');");
        }
    }
    
    public function delFavorite($table, $usergid, $gid) {
        try {
            $this->exec("DELETE FROM " . $table . " WHERE user='$usergid' AND gid='$gid';");
        } catch (Exception $e) {
            // Don't care if this doesn't work
        }
    }
    
    public function getFavorites($table, $usergid) {
        $result = $this->query("SELECT gid as id FROM $table WHERE user='$usergid';");
        return $result ? $result->fetchAll(PDO::FETCH_COLUMN) : array();
    }
}

class SimpleRatings extends SimpleDatabase
{
    function getLastUpdate($user) {
        // NOTE: Currenetly returns last update for *any* user.  Close enough
        //       for what we're doing.
        return $this->getUpdateTime();
    }
    
    public function addRating($user, $gid, $rating) {
	//0 rating means that we want to "unrate" the item, e.g remove it from the table
	if($rating == 0) {
	    $this->delRating($user, $gid);
        }
	else {
            $result = $this->query("SELECT COUNT(1) FROM tracks WHERE user='$user' AND gid='$gid';");

            $count = $result ? $result->fetchColumn() : 0;
            print "count: $count\n";
            if ($count == 0) {
               $this->exec("INSERT INTO tracks(user,gid,rating) VALUES('$user', '$gid', '$rating');");
            }
  	    else {
               $this->exec("UPDATE tracks SET rating=$rating WHERE user='$user' AND gid='$gid';");
            }		
	}
    }

    public function delRating($user, $gid) {
        try {
            $this->exec("DELETE FROM tracks WHERE user='$user' AND gid='$gid';");
        } catch (Exception $e) {
            // Don't care if this doesn't work
        }
    }

    public function getRating($user, $gid) {
        $result = $this->query("SELECT rating FROM tracks WHERE user='$user' AND gid='$gid';");
        return (int)($result ? $result->fetchColumn() : 0);
    }
   
    public function getRatingsForUser($user) {
        $result = $this->query("SELECT gid, rating FROM tracks WHERE user='$user';");
        return $result ? $result->fetchAll() : array();
    }

}

class SimpleUserManager extends SimpleDatabase
{
    public function isValidUserName($user) {
        return 1;
    }
    
    public function addUser($user, $password) {
        
        if (! $this->isValidUserName($user)) {
            return "";
        }

        //
        // Yup, this is not remotely safe for high concurrency.  I opted not to
        // use transactions here as I really don't expect people will be
        // beating on adding users to the sample app.  In fact, I think most
        // will just use the users that ship in the database (which were
        // generated using this call).
        //
        $result = $this->query("SELECT gid FROM users WHERE user='$user';");
        $row    = $result->fetch();
        if (isset($row['gid'])) {
            return "";
        }

        // Find a new gid
        $gid = "";
        for ($try = 16; $try > 0; $try--) {
            
            $gid = uniqid();
            
            $result = $this->query("SELECT gid FROM users WHERE gid='$gid';");
            $row    = $result->fetch();

            if (!isset($row['gid'])) {
                break;
            }
        }

        if ($try == 0) {
            return "";
        }
        
        // Insert the user
        $md5 = md5($password);
        $this->exec("INSERT INTO users(gid,user,password) VALUES('$gid','$user','$md5');");

        return $gid;
    }

    public function changePassword($user, $password) {
        // Big hammer.  Use wisely.  Or not at all...
        $sth = $this->prepare("UPDATE users SET password = ? WHERE user = ?;");
        $sth->execute(array(md5($password),$user));
    }

    public function getSessionId($user, $password) {

        if (!$this->isValidUserName($user)) {
            return "notvalid";
        }

        //
        // Look up the user
        //
        $results = $this->query("SELECT gid,password FROM users WHERE user='$user';");
        foreach ($results as $result) {
            $gid    = $result['gid'];
            $md5    = $result['password'];            
        }
        
        // Hit?
        if (!isset($md5) || !isset($gid)) {
            return "noaccount";
        }
        
        // Is the password correct?
        if ($md5 != md5($password)) {
            return "badpassword";
        }

        //
        // Generate a sessionid from the gid, cleverly stashing a creation time
        // on it so we can expire it.  Not the most secure thing in the world,
        // but fine for a sample app.  A production app would have actual
        // session tokens that are checked and actually expire via some
        // mechanism that is not based on the contents on the token itself (way
        // too easy to spoof).
        //
        return sprintf("%s:%d", $gid, intval(time() / 60));
    }
    
    public function getUser($sessionid) {
        //
        // sessionids should be gid:time
        //
        $parts   = explode(":",$sessionid,2);
        $gid     = array_shift($parts);
        $created = intval(array_shift($parts));

        //
        // Expired?  We expire in 60 minutes in this example.  This doesn't cost
        // us that much (or save us that much) either way.
        //
        $expires = $created + 60;

        if (intval(time() / 60) > $expires) {
            return "";
        }

        //
        // Return the gid directly.  Again, *bad* idea in production.  If the
        // user is no longer valid we should return an empty user so that we
        // drop the session.
        //
        return $gid;
    }
}

?>
