function ThisInterface(id) {
    this.id = id;
    this.label = '#' + this.id;
}

ThisInterface.prototype

ThisInterface.prototype.gen = function(){
    $(this.label).append('');
}