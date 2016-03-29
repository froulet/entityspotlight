function isAddOrDeletion(elem, categories) {
    if($.inArray(elem, categories) != -1)
        {
            return true;           
        }

        else
        {
            return false;        
        }
}

Array.prototype.diff = function (arr) {

        // Merge the arrays
        var mergedArr = this.concat(arr);

        // Get the elements which are unique in the array
        // Return the diff array
        return mergedArr.filter(function (e) {
            // Check if the element is appearing only once
            return mergedArr.indexOf(e) === mergedArr.lastIndexOf(e);
        });
};