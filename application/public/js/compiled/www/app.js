(function() {
  Number.prototype.formatMoney = function(decPlaces, thouSeparator, decSeparator) {
    var i, j, n, sign;
    if (decPlaces == null) {
      decPlaces = 2;
    }
    if (thouSeparator == null) {
      thouSeparator = ',';
    }
    if (decSeparator == null) {
      decSeparator = '.';
    }
    n = this;
    decPlaces = (isNaN(decPlaces = Math.abs(decPlaces)) ? 2 : decPlaces);
    decSeparator = (decSeparator === undefined ? "." : decSeparator);
    thouSeparator = (thouSeparator === undefined ? "," : thouSeparator);
    sign = (n < 0 ? "-" : "");
    i = parseInt(n = Math.abs(+n || 0).toFixed(decPlaces)) + "";
    j = ((j = i.length) > 3 ? j % 3 : 0);
    return sign + (j ? i.substr(0, j) + thouSeparator : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thouSeparator) + (decPlaces ? decSeparator + Math.abs(n - i).toFixed(decPlaces).slice(2) : "");
  };

  window.streameApp = angular.module('streameApp', ['ngRoute', 'ngCookies', 'ngSanitize']);

  streameApp.config(function($routeProvider, $locationProvider, $compileProvider) {
    $locationProvider.html5Mode(true);
    return $routeProvider.when('/', {
      templateUrl: 'browse.html',
      controller: 'BrowseController'
    }).when('/p/:postId', {
      templateUrl: 'pane.html',
      controller: 'PaneController'
    }).otherwise({
      redirectTo: '/'
    });
  });

  streameApp.controller("FrameController", function($scope, $location) {});

  streameApp.factory('Image', function() {
    var Image;
    return Image = (function() {
      Image.prototype.data = {};

      function Image(data) {
        this.data = data != null ? data : {};
      }

      Image.prototype.resize = function(size) {
        if (size == null) {
          size = 'x';
        }
        return {
          src: this.data.src.replace('/x/', "/" + size + "/")
        };
      };

      return Image;

    })();
  });

  streameApp.factory('Api', function($http, cookies) {
    return {
      token: function(token) {
        console.log("SETTING TOKEN: %o: %o", token);
        return cookies.set('token', token);
      },
      _post: function(path, data, cb) {
        return $http.post(path, data).success(function(r) {
          return cb(r.data, null);
        }).error(function(r) {
          return cb(r.data, r.message);
        });
      },
      _apost: function(path, data, cb) {
        $http.defaults.headers.common['X-Auth-Token'] = cookies.get('token');
        return $http.post(path, data).success(function(r) {
          return cb(r.data, null);
        }).error(function(r) {
          return cb(r.data, r.message);
        });
      },
      _aget: function(path, data, cb) {
        $http.defaults.headers.common['X-Auth-Token'] = cookies.get('token');
        return $http.get(path, data).success(function(r) {
          return cb(r.data, null);
        }).error(function(r) {
          return cb(r.data, r.message);
        });
      }
    };
  });

  streameApp.factory('cookies', function() {
    return {
      set: function(name, value, ts) {
        var date, expires;
        if (ts == null) {
          ts = 0;
        }
        if (ts) {
          date = new Date();
          date.setTime(date.getTime() + ts);
          expires = "; expires=" + date.toGMTString();
        } else {
          expires = "";
        }
        return document.cookie = name + "=" + value + expires + "; path=/";
      },
      get: function(name) {
        var c, ca, i, nameEQ;
        nameEQ = name + "=";
        ca = document.cookie.split(";");
        i = 0;
        while (i < ca.length) {
          c = ca[i];
          while (c.charAt(0) === " ") {
            c = c.substring(1, c.length);
          }
          if (c.indexOf(nameEQ) === 0) {
            return c.substring(nameEQ.length, c.length);
          }
          i++;
        }
        return null;
      },
      "delete": function(name) {
        return this.set(name, "", -1);
      }
    };
  });

  streameApp.factory('User', function(Api, cookies) {
    return {
      data: {},
      load: function(cb) {
        var _this = this;
        if (!cookies.get('token')) {
          return cb(null, 'Token not set');
        }
        return Api._aget('/api/session', {}, function(r, err) {
          if (!err) {
            console.log("Loaded user: %o", r.user);
            _this.data = r.user;
          }
          return cb(r.user, err);
        });
      },
      faker: function(cb) {
        return this.register(Math.floor(Math.random() * 1000000000) + '@example.org', 'password', 'Jimmy', cb);
      },
      register: function(email, password, display_name, cb) {
        var data;
        data = {
          email: email,
          display_name: display_name,
          password: password
        };
        return Api._post('/api/session', data, function(data, err) {
          return cb(data, err);
        });
      },
      recordAction: function(target_type, target_id, action, rating, cb) {
        var _this = this;
        console.log("RECORD ACTION");
        return Api._apost('/api/user/actions', {
          target_type: target_type,
          target_id: target_id,
          action: action,
          rating: rating
        }, function(r, err) {
          if (err) {
            console.log("Loaded user: %o", r.user);
            _this.data = r.action;
          }
          return cb(r.data, err);
        });
      }
    };
  });

  streameApp.factory('Post', function(Image, Api) {
    var Post;
    return Post = (function() {
      Post.prototype.raw_data = {};

      function Post(data) {
        if (data == null) {
          data = null;
        }
        if (data) {
          this.load(data);
        }
      }

      Post.fetch = function(id, cb) {
        var _this = this;
        return Api._aget("/api/posts/" + id, {}, function(r, err) {
          var post;
          if (!err) {
            console.log("Loaded post: %o", r.post);
          }
          post = new _this;
          post.load(r.post);
          return cb(post, err);
        });
      };

      Post.prototype.load = function(data) {
        var k, v;
        for (k in data) {
          v = data[k];
          this[k] = v;
        }
        console.log("Post: %o", data);
        if (this.main_image) {
          this.main_image = new Image(this.main_image);
        } else {
          this.main_image = null;
        }
        return this.raw_data = data;
      };

      return Post;

    })();
  });

  streameApp.factory('Posts', function($http, Post, Api) {
    return {
      fetch: function(query, cb) {
        var _this = this;
        return Api._aget('/api/posts', {
          params: query
        }, function(r, err) {
          var post, posts, _i, _len, _ref;
          if (err) {
            console.error("Error: %o", err);
            return cb(r, err);
          }
          posts = [];
          _ref = r.posts;
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            post = _ref[_i];
            posts.push(new Post(post));
          }
          return cb({
            posts: posts
          });
        });
      }
    };
  });

  streameApp.controller("PaneController", function($scope, $rootScope, $routeParams, $location, User, Api, Post, $sce) {
    $scope.post = null;
    return Post.fetch($routeParams.postId, function(post) {
      $scope.post = post;
      return $scope.paneSrc = $sce.trustAsResourceUrl($scope.post.url);
    });
  });

  streameApp.controller("BrowseController", function($scope, $rootScope, $location, User, Api, Posts) {
    $scope.posts = [];
    $scope.current_ids = {};
    $scope.page = 0;
    $scope.post_count = 1;
    $scope.isLoading = false;
    $scope.loadNext = function() {
      var limit, offset;
      if ($scope.isLoading) {
        return;
      }
      $scope.isLoading = true;
      offset = $scope.page * $scope.post_count;
      limit = $scope.post_count;
      $scope.page++;
      return Posts.fetch({
        offset: offset,
        limit: limit
      }, function(r) {
        var post, _i, _len, _ref, _results;
        $scope.isLoading = false;
        _ref = r.posts;
        _results = [];
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          post = _ref[_i];
          if ($scope.current_ids[post.id] == null) {
            $scope.current_ids[post.id] = post.id;
            _results.push($scope.posts.push(post));
          } else {
            _results.push(void 0);
          }
        }
        return _results;
      });
    };
    $scope.action = function(target_type, target_id, action, rating) {
      if (rating == null) {
        rating = null;
      }
      return User.recordAction(target_type, target_id, action, rating, function(d, err) {
        return console.log("RECORDED: %o", d);
      });
    };
    return User.load(function(data, err) {
      console.log("USER: %o", data);
      if (err) {
        return User.faker(function(data, err) {
          Api.token(data.token);
          return $scope.loadNext();
        });
      } else {
        return $scope.loadNext();
      }
    });
  });

}).call(this);
