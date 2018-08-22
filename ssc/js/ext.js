/* 复制一份（值传递） */
if (!Object.prototype.clone || !Array.prototype.clone) {
    Object.prototype.clone = Array.prototype.clone = function () {
        var str, newobj = this.constructor === Array ? [] : {};
        if (typeof this !== 'object') {
            return;
        } else if (window.JSON) {
            str = JSON.stringify(this); //序列化对象
            newobj = JSON.parse(str); //还原
        } else {
            for (var i in this) {
                newobj[i] = typeof this[i] === 'object' ? clone(this[i]) : this[i];
            }
        }
        return newobj;
    };
    Object.defineProperty(Array.prototype, 'clone', {enumerable: false});
    Object.defineProperty(Object.prototype, 'clone', {enumerable: false});
}

/* 求数组和 */
if (!Array.prototype.sum) {
    Object.defineProperty(Array.prototype, 'sum', {
        value: function () {
            return this.reduce(function (acc, cur) {
                return acc + cur;
            }, 0);
        }
    });
}

/* 返回数组元素所对应的下一个元素 */
if (!Array.prototype.next) {
    Object.defineProperty(Array.prototype, 'next', {
        value: function (val) {
            for (var i = 0, len = this.length; i < len; ++i) {
                if (this[i] === val) {
                    return this[i + 1] !== undefined ? this[i + 1] : this[0];
                }
            }
            return val;
        }
    });
}

/* 实现repeat */
if (!String.prototype.repeat) {
    Object.defineProperty(String.prototype, 'repeat', {
        value: function (count) {
            'use strict';
            if (this == null) {
                throw new TypeError('can\'t convert ' + this + ' to object');
            }
            var str = '' + this;
            count = +count;
            if (count !== count) {
                count = 0;
            }
            if (count < 0) {
                throw new RangeError('repeat count must be non-negative');
            }
            if (count === Infinity) {
                throw new RangeError('repeat count must be less than infinity');
            }
            count = Math.floor(count);
            if (str.length === 0 || count === 0) {
                return '';
            }
            // 确保 count 是一个 31 位的整数。这样我们就可以使用如下优化的算法。
            // 当前（2014年8月），绝大多数浏览器都不能支持 1 << 28 长的字符串，所以：
            if (str.length * count >= 1 << 28) {
                throw new RangeError('repeat count must not overflow maximum string size');
            }
            var rpt = '';
            for (; ;) {
                if ((count & 1) === 1) {
                    rpt += str;
                }
                count >>>= 1;
                if (count === 0) {
                    break;
                }
                str += str;
            }
            return rpt;
        }
    });
}

// https://tc39.github.io/ecma262/#sec-array.prototype.includes
if (!Array.prototype.includes) {
    Object.defineProperty(Array.prototype, 'includes', {
        value: function (searchElement, fromIndex) {

            // 1. Let O be ? ToObject(this value).
            if (this == null) {
                throw new TypeError('"this" is null or not defined');
            }

            var o = Object(this);

            // 2. Let len be ? ToLength(? Get(O, "length")).
            var len = o.length >>> 0;

            // 3. If len is 0, return false.
            if (len === 0) {
                return false;
            }

            // 4. Let n be ? ToInteger(fromIndex).
            //    (If fromIndex is undefined, this step produces the value 0.)
            var n = fromIndex | 0;

            // 5. If n ≥ 0, then
            //  a. Let k be n.
            // 6. Else n < 0,
            //  a. Let k be len + n.
            //  b. If k < 0, let k be 0.
            var k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);

            // 7. Repeat, while k < len
            while (k < len) {
                // a. Let elementK be the result of ? Get(O, ! ToString(k)).
                // b. If SameValueZero(searchElement, elementK) is true, return true.
                // c. Increase k by 1.
                // NOTE: === provides the correct "SameValueZero" comparison needed here.
                if (o[k] === searchElement) {
                    return true;
                }
                k++;
            }

            // 8. Return false
            return false;
        }
    });
}