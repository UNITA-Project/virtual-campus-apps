import styled from 'styled-components';
import mountain from '../Img/mountain.jpg';

export const FooterContainer = styled.div`
    justify-content: center;
    align-items: center;
    display: flex;
    bottom: 0;
    top: 95%;
    position: absolute;
    width: 100%;
    height: 5%;
    background: rgba(255,255,255,0.5);
`;

export const CardContainer = styled.div`
    display: flex;
    background-image: url(${mountain});
    width: 100%;
    height: 100vh;
    background-repeat: no-repeat;
    background-position: center;
    background-size: cover;
    justify-content: center;
    align-content: center;
    align-items: center;
    @media screen and (max-width: 650px) {
        position: fixed; 
        overflow-y: none;   
    }
`;

export const PageHolder = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 75px;
  background-image: url(${mountain});
  background-attachment: fixed;
  background-position: center;
  width: 100vw;
  height: 100vh;

  @media screen and (min-width: 1000px) and (max-height: 700px) {
    gap: 40px;
  }

  @media screen and (max-width: 1000px) {
    gap: 40px;

    @media screen and (max-width: 650px) {
      gap: 20px;
      position: fixed; 
      overflow-y: none;   
    }
  }
`;

export const GridHolder = styled.div`
  display: grid;
  grid-template-columns: repeat(3, 300px);
  grid-template-rows: repeat(2, 200px);
  column-gap: 20px;
  row-gap: 20px;

  @media screen and (min-width: 1000px) and (max-height: 700px) {
	grid-template-rows: repeat(2, 150px);
  }

  @media screen and (max-width: 1000px) {
    display: grid;
    grid-template-columns: repeat(2, 300px);
    grid-template-rows: repeat(3, 125px);

    @media screen and (max-width: 650px) {
      display: grid;
      grid-template-columns: repeat(2, 150px);
      grid-template-rows: repeat(3, 100px);  
	  column-gap: 10px;
	  row-gap: 10px;
	}
  }
`;

export const ButtonsHolder = styled.div`
    display: flex;
    justify-content: space-around;
    width: 500px;

    @media screen and (max-width: 1000px) {
    margin-right: 15px;

        @media screen and (max-width: 650px) {
            flex-direction: column;
            gap: 5px;
            align-items: center;
            margin-right: 10px;
        }
    }
`;

export const GridItem = styled.div`
	height: 100%;
	width: 100%;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	align-items: center;
	box-shadow: 5px 10px 8px rgba(0, 0, 0, 0.486);
	background-color: white;
	border-radius: 15px;
    margin: 5px;

	&>img{
		margin-top:5px;
		width:95%;
		object-fit: contain;
	}

  	&>div>span {
		position: relative;
		margon-bottom: 5px;
	}

	&:hover {
		transition: transform 0.2s;
		transform: scale(1.1);
		cursor:pointer;
	}

	@media screen and (min-width: 1000px) and (max-height: 700px) {
		height: 100%;
		
		&>img{
			margin-top:5px;
			height:40%;
			object-fit: contain;
		}
	}

	@media screen and (max-width: 1000px) {
		height: 100%;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
	
		&>img{
			margin-top:5px;
			width:90%;
			object-fit: contain;
		}
	}

`;

export const MiniGridItemHolder = styled.div`
	height: 100%;
	width: 100%;
	display: flex;
	justify-content: center;
	align-items: center;
`;

export const MiniGridItem = styled.div`
	height: 80%;
	width: 80%;
	display: flex;
	justify-content: center;
	align-items: center;
	box-shadow: 5px 10px 8px rgba(0, 0, 0, 0.486);
	background-color: white;
	padding: 5px;
	border-radius: 15px;
	justify-self: center;

	&>img {
		width: 90%;
		height: 90%;
		object-fit: contain;
	}

	&:hover {
	  transition: transform 0.2s;
	  transform: scale(1.1);
	  cursor:pointer;
	}
`;

export const HeaderContainer = styled.div`
    display: flex;
    position: absolute;
    width: 100%;
    height: 80px;
    background: rgba(255,255,255,0.5);
    align-items: center;
    justify-content: center;
    align-content: center;
`;

export const LogoLangContainer = styled.div`
    position: relative;
    display: flex;
    width: 80%;
    margin-left: 10%;
    margin-right: 10%;
    height: 100%;
    justify-content: space-between;
    align-items: center;
`;
