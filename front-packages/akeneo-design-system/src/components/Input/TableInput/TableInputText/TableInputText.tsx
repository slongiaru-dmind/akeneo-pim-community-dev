import {TableInputRow} from '../TableInputRow/TableInputRow';
import styled, {css} from 'styled-components';
import {TextInput} from '../../TextInput/TextInput';
import {AkeneoThemedProps, getColor} from '../../../../theme';

const TableInputText = styled(TextInput)<
  {highlighted: boolean; inError: boolean; readOnly: boolean} & AkeneoThemedProps
>`
  height: 39px;
  padding-left: 10px;
  padding-right: ${({readOnly}) => (readOnly ? '35px' : '10px')};
  border-radius: 0;
  border: none;
  background: none;

  ${({highlighted, inError}) =>
    highlighted &&
    !inError &&
    css`
      background: ${getColor('green', 10)};
      box-shadow: 0 0 0 1px ${getColor('green', 80)};
    `};

  ${({inError}) =>
    inError &&
    css`
      background: ${getColor('red', 10)};
      box-shadow: 0 0 0 1px ${getColor('red', 80)};
    `};

  &:focus {
    box-shadow: 0 0 0 1px ${getColor('grey', 100)};
  }
`;

TableInputRow.displayName = 'TableInput.TextInput';

export {TableInputText};