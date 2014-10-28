Number::formatMoney = (decPlaces = 2, thouSeparator = ',', decSeparator = '.') ->
  n = this
  decPlaces = (if isNaN(decPlaces = Math.abs(decPlaces)) then 2 else decPlaces)
  decSeparator = (if decSeparator is `undefined` then "." else decSeparator)
  thouSeparator = (if thouSeparator is `undefined` then "," else thouSeparator)
  sign = (if n < 0 then "-" else "")
  i = parseInt(n = Math.abs(+n or 0).toFixed(decPlaces)) + ""
  j = (if (j = i.length) > 3 then j % 3 else 0)
  sign + ((if j then i.substr(0, j) + thouSeparator else "")) + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thouSeparator) + ((if decPlaces then decSeparator + Math.abs(n - i).toFixed(decPlaces).slice(2) else ""))
  
window.streameApp = 
  angular.module 'streameApp',
  [
    'ngRoute',
    'ngCookies',
    'ngSanitize'
  ]

streameApp.config ($routeProvider, $locationProvider, $compileProvider) ->

  $locationProvider.html5Mode true
 
  $routeProvider
    .when('/',
      templateUrl: '/partials/browse.html'
      controller:  'BrowseController'
    ).when('/p/:postId',
      templateUrl: '/partials/pane.html'
      controller:  'PaneController'
    ).otherwise( redirectTo: '/' )
  
streameApp.controller "FrameController", ($scope,$location) ->
  
streameApp.factory 'Image', () ->
  return class Image
    data: {}
    constructor: (@data = {}) ->
    resize: (size='x') ->
      src: @data.src.replace('/x/', "/#{size}/")
  
streameApp.factory 'Api', ($http, cookies) ->
  token: (token)->
    console.log "SETTING TOKEN: %o: %o", token
    cookies.set 'token',token
  _post: (path, data, cb)->
    $http.post(path, data)
      .success (r)->
        cb r.data, null
      .error (r)->
        cb r.data, r.message
  _apost: (path, data, cb)->
    $http.defaults.headers.common['X-Auth-Token'] = cookies.get('token')
    $http.post(path, data)
      .success (r)->
        cb r.data, null
      .error (r)->
        cb r.data, r.message
  _aget: (path, data, cb)->
    $http.defaults.headers.common['X-Auth-Token'] = cookies.get('token')
    $http
      .get(path, data)
      .success (r)->
        cb r.data, null
      .error (r)->
        cb r.data, r.message

streameApp.factory 'cookies', () ->
  set: (name, value, ts = 0) ->
    if ts
      date = new Date()
      date.setTime date.getTime() + ts
      expires = "; expires=" + date.toGMTString()
    else
      expires = ""
    document.cookie = name + "=" + value + expires + "; path=/"
  
  get: (name) ->
    nameEQ = name + "="
    ca = document.cookie.split(";")
    i = 0
  
    while i < ca.length
      c = ca[i]
      c = c.substring(1, c.length)  while c.charAt(0) is " "
      return c.substring(nameEQ.length, c.length)  if c.indexOf(nameEQ) is 0
      i++
    null
  
  delete: (name) ->
    @set name, "", -1
        
streameApp.factory 'User', (Api, cookies) ->
  data: {}
  load: (cb) ->
    if !cookies.get 'token'
      return cb(null, 'Token not set')
    Api._aget '/api/session', {}, (r, err)=>
      if !err
        console.log "Loaded user: %o", r.user
        @data = r.user
      cb(r.user, err)
  faker: (cb)->
    @register Math.floor(Math.random()*1000000000) + '@example.org', 'password', 'Jimmy', cb 
  register: (email,password, display_name, cb) ->
    data = 
      email:        email
      display_name: display_name 
      password:     password
    Api._post '/api/session', data, (data, err)->
      cb data, err
  recordAction: (target_type,target_id, action, rating,  cb) ->
    console.log "RECORD ACTION"
    Api._apost '/api/user/actions', {target_type:target_type, target_id:target_id,action:action, rating:rating}, (r, err)=>
      if err
        console.log "Loaded user: %o", r.user
        @data = r.action
      cb(r.data, err)
    
streameApp.factory 'Post', (Image, Api) ->
  return class Post
    raw_data: {}
    constructor: (data = null) ->
      if data
        @load(data) 
    @fetch: (id, cb)->

      Api._aget "/api/posts/#{id}", {}, (r, err)=>
        if !err
          console.log "Loaded post: %o", r.post
        
        post = new this
        post.load r.post
        
        cb(post, err)
        
    load: (data) ->
      for k,v of data
        @[k] = v  
      console.log "Post: %o", data
      if @main_image
        @main_image = new Image @main_image
      else 
        @main_image = null
    
      @raw_data = data
      
streameApp.factory 'Posts', ($http,Post,Api)->
  fetch: (query, cb) ->
    Api._aget '/api/posts', {params: query}, (r, err)=>
      if err
        console.error "Error: %o", err;
        return cb r, err
      posts = []

      for post in r.posts
        posts.push new Post post
        
      cb posts: posts
    
streameApp.controller "PaneController", ($scope, $rootScope, $routeParams, $location, User, Api, Post, $sce) ->
  
  $scope.post = null
  Post.fetch $routeParams.postId, (post)->
    $scope.post = post
    $scope.paneSrc = $sce.trustAsResourceUrl($scope.post.url);
    
streameApp.controller "BrowseController", ($scope, $rootScope, $location, User, Api, Posts) ->
  
  $scope.posts       = []
  $scope.current_ids = {}
  
  $scope.page       = 0
  $scope.post_count = 1;
  
  $scope.isLoading  = false
  $scope.loadNext = ()-> 
    if $scope.isLoading 
      return
    $scope.isLoading = true
    offset = $scope.page * $scope.post_count
    limit  = $scope.post_count
    $scope.page++
    Posts.fetch {offset: offset, limit: limit}, (r) ->
    
      $scope.isLoading = false
      for post in r.posts
        if !$scope.current_ids[post.id]?
          $scope.current_ids[post.id] = post.id
          $scope.posts.push post  
      
  $scope.action = (target_type, target_id, action, rating = null) ->
    User.recordAction target_type, target_id, action, rating, (d, err)->
      console.log "RECORDED: %o", d
    
  User.load (data, err)->
    console.log "USER: %o", data
    if err
      User.faker (data, err)->
        Api.token data.token
        $scope.loadNext()
    else
      $scope.loadNext()
        

        
      
    
  
  
