/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/*! Based on http://www.webtoolkit.info/javascript_md5.html */

class Md5 {
  public static hash(value: string): string {
    let x;
    let k;
    let AA;
    let BB;
    let CC;
    let DD;
    let a;
    let b;
    let c;
    let d;
    const S11 = 7;
    const S12 = 12;
    const S13 = 17;
    const S14 = 22;
    const S21 = 5;
    const S22 = 9;
    const S23 = 14;
    const S24 = 20;
    const S31 = 4;
    const S32 = 11;
    const S33 = 16;
    const S34 = 23;
    const S41 = 6;
    const S42 = 10;
    const S43 = 15;
    const S44 = 21;

    value = Md5.utf8Encode(value);
    x = Md5.convertToWordArray(value);

    a = 0x67452301;
    b = 0xEFCDAB89;
    c = 0x98BADCFE;
    d = 0x10325476;

    for (k = 0; k < x.length; k += 16) {
      AA = a;
      BB = b;
      CC = c;
      DD = d;
      a = Md5.FF(a, b, c, d, x[k], S11, 0xD76AA478);
      d = Md5.FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
      c = Md5.FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
      b = Md5.FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
      a = Md5.FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
      d = Md5.FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
      c = Md5.FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
      b = Md5.FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
      a = Md5.FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
      d = Md5.FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
      c = Md5.FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
      b = Md5.FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
      a = Md5.FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
      d = Md5.FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
      c = Md5.FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
      b = Md5.FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
      a = Md5.GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
      d = Md5.GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
      c = Md5.GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
      b = Md5.GG(b, c, d, a, x[k], S24, 0xE9B6C7AA);
      a = Md5.GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
      d = Md5.GG(d, a, b, c, x[k + 10], S22, 0x2441453);
      c = Md5.GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
      b = Md5.GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
      a = Md5.GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
      d = Md5.GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
      c = Md5.GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
      b = Md5.GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
      a = Md5.GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
      d = Md5.GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
      c = Md5.GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
      b = Md5.GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
      a = Md5.HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
      d = Md5.HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
      c = Md5.HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
      b = Md5.HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
      a = Md5.HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
      d = Md5.HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
      c = Md5.HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
      b = Md5.HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
      a = Md5.HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
      d = Md5.HH(d, a, b, c, x[k], S32, 0xEAA127FA);
      c = Md5.HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
      b = Md5.HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
      a = Md5.HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
      d = Md5.HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
      c = Md5.HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
      b = Md5.HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
      a = Md5.II(a, b, c, d, x[k], S41, 0xF4292244);
      d = Md5.II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
      c = Md5.II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
      b = Md5.II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
      a = Md5.II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
      d = Md5.II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
      c = Md5.II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
      b = Md5.II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
      a = Md5.II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
      d = Md5.II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
      c = Md5.II(c, d, a, b, x[k + 6], S43, 0xA3014314);
      b = Md5.II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
      a = Md5.II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
      d = Md5.II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
      c = Md5.II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
      b = Md5.II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
      a = Md5.addUnsigned(a, AA);
      b = Md5.addUnsigned(b, BB);
      c = Md5.addUnsigned(c, CC);
      d = Md5.addUnsigned(d, DD);
    }

    let temp = Md5.wordToHex(a) + Md5.wordToHex(b) + Md5.wordToHex(c) + Md5.wordToHex(d);

    return temp.toLowerCase();
  }

  private static rotateLeft(lValue: number, iShiftBits: number): number {
    return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
  }

  private static addUnsigned(lX: number, lY: number): number {
    let lX8 = (lX & 0x80000000);
    let lY8 = (lY & 0x80000000);
    let lX4 = (lX & 0x40000000);
    let lY4 = (lY & 0x40000000);
    let lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
    if (lX4 & lY4) {
      return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
    }
    if (lX4 | lY4) {
      if (lResult & 0x40000000) {
        return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
      } else {
        return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
      }
    } else {
      return (lResult ^ lX8 ^ lY8);
    }
  }

  private static F(x: number, y: number, z: number): number {
    return (x & y) | ((~x) & z);
  }

  private static G(x: number, y: number, z: number): number {
    return (x & z) | (y & (~z));
  }

  private static H(x: number, y: number, z: number): number {
    return (x ^ y ^ z);
  }

  private static I(x: number, y: number, z: number): number {
    return (y ^ (x | (~z)));
  }

  private static FF(a: number, b: number, c: number, d: number, x: number, s: number, ac: number): number {
    a = Md5.addUnsigned(a, Md5.addUnsigned(Md5.addUnsigned(Md5.F(b, c, d), x), ac));
    return Md5.addUnsigned(Md5.rotateLeft(a, s), b);
  }

  private static GG(a: number, b: number, c: number, d: number, x: number, s: number, ac: number): number {
    a = Md5.addUnsigned(a, Md5.addUnsigned(Md5.addUnsigned(Md5.G(b, c, d), x), ac));
    return Md5.addUnsigned(Md5.rotateLeft(a, s), b);
  }

  private static HH(a: number, b: number, c: number, d: number, x: number, s: number, ac: number): number {
    a = Md5.addUnsigned(a, Md5.addUnsigned(Md5.addUnsigned(Md5.H(b, c, d), x), ac));
    return Md5.addUnsigned(Md5.rotateLeft(a, s), b);
  }

  private static II(a: number, b: number, c: number, d: number, x: number, s: number, ac: number): number {
    a = Md5.addUnsigned(a, Md5.addUnsigned(Md5.addUnsigned(Md5.I(b, c, d), x), ac));
    return Md5.addUnsigned(Md5.rotateLeft(a, s), b);
  }

  private static convertToWordArray(string: string): Array<number> {
    let lWordCount;
    let lMessageLength = string.length;
    let lNumberOfWords_temp1 = lMessageLength + 8;
    let lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
    let lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
    let lWordArray = Array(lNumberOfWords - 1);
    let lBytePosition = 0;
    let lByteCount = 0;
    while (lByteCount < lMessageLength) {
      lWordCount = (lByteCount - (lByteCount % 4)) / 4;
      lBytePosition = (lByteCount % 4) * 8;
      lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
      lByteCount++;
    }
    lWordCount = (lByteCount - (lByteCount % 4)) / 4;
    lBytePosition = (lByteCount % 4) * 8;
    lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
    lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
    lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
    return lWordArray;
  }

  private static wordToHex(lValue: number): string {
    let wordToHexValue = '';
    let wordToHexValue_temp = '';
    let lByte;
    let lCount;
    for (lCount = 0; lCount <= 3; lCount++) {
      lByte = (lValue >>> (lCount * 8)) & 255;
      wordToHexValue_temp = '0' + lByte.toString(16);
      wordToHexValue = wordToHexValue + wordToHexValue_temp.substr(wordToHexValue_temp.length - 2, 2);
    }
    return wordToHexValue;
  }

  private static utf8Encode(string: string): string {
    string = string.replace(/\r\n/g, '\n');
    let utftext = '';

    for (let n = 0; n < string.length; n++) {
      let c = string.charCodeAt(n);

      if (c < 128) {
        utftext += String.fromCharCode(c);
      } else if ((c > 127) && (c < 2048)) {
        utftext += String.fromCharCode((c >> 6) | 192);
        utftext += String.fromCharCode((c & 63) | 128);
      } else {
        utftext += String.fromCharCode((c >> 12) | 224);
        utftext += String.fromCharCode(((c >> 6) & 63) | 128);
        utftext += String.fromCharCode((c & 63) | 128);
      }

    }

    return utftext;
  }
}

export = Md5;
